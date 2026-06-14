<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Contract\DramaWriterInterface;
use Plugin\ShortDrama\Contract\EpisodeWriterInterface;
use Plugin\ShortDrama\Model\Drama;
use Plugin\ShortDrama\Service\ImportService;

final class ImportServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/shortdrama-import-' . bin2hex(random_bytes(4));
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function testExecuteKeepsValidRowsWhenAnotherRowFails(): void
    {
        $file = $this->writeWorkbook([
            ['external_drama_id', 'title', 'display_author_name', 'author_user_id', 'total_episodes', 'cover_url', 'vip_free', 'status', 'description', 'category', 'tags'],
            ['D1', '短剧一', '作者', 1, 80, 'https://img.test/d1.webp', 0, 1, '', '推荐', '甜宠'],
            ['', '缺少ID', '作者', 1, 20, 'https://img.test/d2.webp', 0, 1, '', '推荐', ''],
            ['D3', '短剧三', '作者', 1, 30, 'https://img.test/d3.webp', 0, 2, '', '都市', ''],
        ]);
        $dramas = Mockery::mock(DramaWriterInterface::class);
        $dramas->shouldReceive('create')->twice()->andReturn(new Drama(), new Drama());
        $episodes = Mockery::mock(EpisodeWriterInterface::class);
        $service = new ImportService($dramas, $episodes, $this->directory, static fn (callable $callback): mixed => $callback());

        $result = $service->execute($file, 'drama');

        self::assertSame(2, $result['success_count'], json_encode($result, JSON_UNESCAPED_UNICODE));
        self::assertSame(1, $result['failure_count']);
        self::assertSame(3, $result['errors'][0]['row']);
        self::assertFileExists($this->directory . '/' . $result['report_id'] . '.json');
        self::assertSame($result, $service->report($result['report_id']));
    }

    public function testValidateRejectsUnexpectedHeaders(): void
    {
        $file = $this->writeWorkbook([['wrong'], ['value']]);
        $service = new ImportService(
            Mockery::mock(DramaWriterInterface::class),
            Mockery::mock(EpisodeWriterInterface::class),
            $this->directory,
            static fn (callable $callback): mixed => $callback()
        );

        $result = $service->validate($file, 'drama');

        self::assertSame(0, $result['success_count']);
        self::assertSame(1, $result['failure_count']);
        self::assertStringContainsString('表头', $result['errors'][0]['message']);
    }

    public function testValidateIgnoresTrailingCellsBeyondTemplateColumns(): void
    {
        $file = $this->writeWorkbook([
            ['external_drama_id', 'title', 'display_author_name', 'author_user_id', 'total_episodes', 'cover_url', 'vip_free', 'status', 'description', 'category', 'tags'],
            ['D1', '短剧一', '作者', 1, 80, 'https://img.test/d1.webp', 0, 1, '', '推荐', '', 'unexpected'],
        ]);
        $service = new ImportService(
            Mockery::mock(DramaWriterInterface::class),
            Mockery::mock(EpisodeWriterInterface::class),
            $this->directory,
            static fn (callable $callback): mixed => $callback()
        );

        $result = $service->validate($file, 'drama');

        self::assertSame(1, $result['success_count']);
        self::assertSame(0, $result['failure_count']);
    }

    public function testValidateRejectsInvalidBusinessValuesBeforeWriting(): void
    {
        $file = $this->writeWorkbook([
            ['external_drama_id', 'title', 'display_author_name', 'author_user_id', 'total_episodes', 'cover_url', 'vip_free', 'status', 'description', 'category', 'tags'],
            ['D1', '短剧一', '作者', 1, -1, 'https://img.test/d1.webp', 3, 9, '', '推荐', ''],
        ]);
        $dramas = Mockery::mock(DramaWriterInterface::class);
        $dramas->shouldNotReceive('create');
        $service = new ImportService(
            $dramas,
            Mockery::mock(EpisodeWriterInterface::class),
            $this->directory,
            static fn (callable $callback): mixed => $callback()
        );

        $result = $service->execute($file, 'drama');

        self::assertSame(0, $result['success_count']);
        self::assertSame(1, $result['failure_count']);
        self::assertStringContainsString('total_episodes', $result['errors'][0]['message']);
    }

    private function writeWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1', true);
        $path = $this->directory . '/input-' . bin2hex(random_bytes(3)) . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        return $path;
    }
}
