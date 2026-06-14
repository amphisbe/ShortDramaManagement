<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Service;

use App\Exception\BusinessException;
use Closure;
use Hyperf\DbConnection\Db;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Plugin\ShortDrama\Contract\DramaWriterInterface;
use Plugin\ShortDrama\Contract\EpisodeWriterInterface;

final class ImportService
{
    private const HEADERS = [
        'drama' => [
            'external_drama_id', 'title', 'display_author_name', 'author_user_id',
            'total_episodes', 'cover_url', 'vip_free', 'status', 'description',
            'category', 'tags',
        ],
        'episode' => [
            'drama_id', 'external_video_id', 'episode_no', 'title', 'play_url',
            'poster_url', 'duration_seconds', 'sort_order', 'status', 'display_nickname',
            'loop', 'play_ing', 'muted', 'is_playing', 'show_title_arrow',
            'show_look_all_btn', 'look_all_btn_text', 'show_bottom_area',
            'bottom_area_btn_text', 'tool_info_json',
        ],
    ];

    private Closure $transaction;

    public function __construct(
        private readonly DramaWriterInterface $dramas,
        private readonly EpisodeWriterInterface $episodes,
        private readonly ?string $reportDirectory = null,
        ?Closure $transaction = null
    ) {
        $this->transaction = $transaction ?? static fn (callable $callback): mixed => Db::connection('drama')->transaction($callback);
    }

    public function validate(string $path, string $type): array
    {
        [$rows, $headerError] = $this->rows($path, $type);
        if ($headerError !== null) {
            return ['success_count' => 0, 'failure_count' => 1, 'errors' => [['row' => 1, 'message' => $headerError]]];
        }

        $success = 0;
        $errors = [];
        foreach ($rows as $rowNumber => $row) {
            $message = $this->validateRow($row, $type);
            if ($message === null) {
                ++$success;
            } else {
                $errors[] = ['row' => $rowNumber, 'message' => $message];
            }
        }
        return ['success_count' => $success, 'failure_count' => count($errors), 'errors' => $errors];
    }

    public function execute(string $path, string $type): array
    {
        [$rows, $headerError] = $this->rows($path, $type);
        $errors = [];
        $success = 0;
        if ($headerError !== null) {
            $errors[] = ['row' => 1, 'message' => $headerError];
        } else {
            foreach ($rows as $rowNumber => $row) {
                $message = $this->validateRow($row, $type);
                if ($message !== null) {
                    $errors[] = ['row' => $rowNumber, 'message' => $message];
                    continue;
                }
                try {
                    ($this->transaction)(function () use ($row, $type): void {
                        $type === 'drama' ? $this->dramas->create($row) : $this->episodes->create($row);
                    });
                    ++$success;
                } catch (\Throwable $throwable) {
                    $errors[] = ['row' => $rowNumber, 'message' => $this->errorMessage($throwable)];
                }
            }
        }

        $result = [
            'success_count' => $success,
            'failure_count' => count($errors),
            'errors' => $errors,
            'report_id' => bin2hex(random_bytes(16)),
        ];
        $this->saveReport($result);
        return $result;
    }

    public function report(string $reportId): array
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $reportId)) {
            throw new \InvalidArgumentException('错误报告编号不合法');
        }
        $path = $this->directory() . '/' . $reportId . '.json';
        if (! is_file($path)) {
            throw new \RuntimeException('错误报告不存在');
        }
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function rows(string $path, string $type): array
    {
        if (! isset(self::HEADERS[$type])) {
            return [[], '导入类型不合法'];
        }
        $data = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        $columnCount = count(self::HEADERS[$type]);
        $headers = array_map(
            static fn (mixed $value): string => trim((string) $value),
            array_slice(array_shift($data) ?? [], 0, $columnCount)
        );
        if ($headers !== self::HEADERS[$type]) {
            return [[], 'Excel 表头与模板不一致'];
        }

        $rows = [];
        foreach ($data as $index => $values) {
            if (count(array_filter($values, static fn (mixed $value): bool => $value !== null && $value !== '')) === 0) {
                continue;
            }
            $normalized = array_slice(array_pad($values, $columnCount, null), 0, $columnCount);
            $rows[$index + 2] = array_combine($headers, $normalized);
        }
        return [$rows, null];
    }

    private function validateRow(array $row, string $type): ?string
    {
        $required = $type === 'drama'
            ? ['external_drama_id', 'title', 'display_author_name', 'author_user_id', 'total_episodes', 'cover_url', 'vip_free', 'status', 'category']
            : self::HEADERS['episode'];
        foreach ($required as $field) {
            if (! array_key_exists($field, $row) || $row[$field] === null || $row[$field] === '') {
                return $field . ' 不能为空';
            }
        }

        return $type === 'drama' ? $this->validateDramaRow($row) : $this->validateEpisodeRow($row);
    }

    private function validateDramaRow(array $row): ?string
    {
        foreach ([
            'external_drama_id' => 24,
            'title' => 255,
            'display_author_name' => 100,
            'cover_url' => 1024,
            'category' => 50,
        ] as $field => $max) {
            if (! is_string($row[$field]) || mb_strlen($row[$field]) > $max) {
                return sprintf('%s 必须是长度不超过 %d 的文本', $field, $max);
            }
        }
        foreach (['author_user_id' => 1, 'total_episodes' => 1] as $field => $minimum) {
            if (! $this->isIntegerAtLeast($row[$field], $minimum)) {
                return sprintf('%s 必须是大于等于 %d 的整数', $field, $minimum);
            }
        }
        if (! $this->isIntegerIn($row['vip_free'], [0, 1])) {
            return 'vip_free 只能为 0 或 1';
        }
        if (! $this->isIntegerIn($row['status'], [0, 1, 2])) {
            return 'status 只能为 0、1 或 2';
        }
        foreach (['description', 'tags'] as $field) {
            if ($row[$field] !== null && ! is_string($row[$field])) {
                return $field . ' 必须是文本';
            }
        }
        return null;
    }

    private function validateEpisodeRow(array $row): ?string
    {
        foreach ([
            'external_video_id' => 24,
            'title' => 500,
            'play_url' => 1024,
            'poster_url' => 1024,
            'display_nickname' => 100,
            'look_all_btn_text' => 255,
            'bottom_area_btn_text' => 255,
        ] as $field => $max) {
            if (! is_string($row[$field]) || mb_strlen($row[$field]) > $max) {
                return sprintf('%s 必须是长度不超过 %d 的文本', $field, $max);
            }
        }
        foreach (['drama_id' => 1, 'episode_no' => 1, 'duration_seconds' => 0, 'sort_order' => 0] as $field => $minimum) {
            if (! $this->isIntegerAtLeast($row[$field], $minimum)) {
                return sprintf('%s 必须是大于等于 %d 的整数', $field, $minimum);
            }
        }
        foreach (['status', 'loop', 'play_ing', 'muted', 'is_playing', 'show_title_arrow', 'show_look_all_btn', 'show_bottom_area'] as $field) {
            if (! $this->isIntegerIn($row[$field], [0, 1])) {
                return $field . ' 只能为 0 或 1';
            }
        }
        if (! is_string($row['tool_info_json'])) {
            return 'tool_info_json 必须是文本';
        }
        return null;
    }

    private function isIntegerAtLeast(mixed $value, int $minimum): bool
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        return $integer !== false && $integer >= $minimum;
    }

    private function isIntegerIn(mixed $value, array $allowed): bool
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        return $integer !== false && in_array($integer, $allowed, true);
    }

    private function errorMessage(\Throwable $throwable): string
    {
        return $throwable instanceof BusinessException
            ? (string) $throwable->getResponse()->message
            : $throwable->getMessage();
    }

    private function saveReport(array $result): void
    {
        $directory = $this->directory();
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException('无法创建导入报告目录');
        }
        file_put_contents(
            $directory . '/' . $result['report_id'] . '.json',
            json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function directory(): string
    {
        return $this->reportDirectory ?? BASE_PATH . '/runtime/import-reports';
    }
}
