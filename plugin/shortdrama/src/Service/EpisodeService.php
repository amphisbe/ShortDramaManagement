<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Service;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use Plugin\ShortDrama\Contract\EpisodeRepositoryInterface;
use Plugin\ShortDrama\Contract\EpisodeWriterInterface;
use Plugin\ShortDrama\Model\DramaEpisode;

final class EpisodeService implements EpisodeWriterInterface
{
    private const FIELDS = [
        'drama_id', 'external_video_id', 'episode_no', 'title', 'play_url',
        'poster_url', 'duration_seconds', 'sort_order', 'status', 'display_nickname',
        'loop', 'play_ing', 'muted', 'is_playing', 'show_title_arrow',
        'show_look_all_btn', 'look_all_btn_text', 'show_bottom_area',
        'bottom_area_btn_text', 'tool_info_json',
    ];

    public function __construct(private readonly EpisodeRepositoryInterface $repository) {}

    public function page(array $params, ?int $page = null, ?int $pageSize = null): array
    {
        return $this->repository->page($params, $page, $pageSize);
    }

    public function find(int $id): DramaEpisode
    {
        return $this->repository->findById($id)
            ?? throw new BusinessException(ResultCode::NOT_FOUND);
    }

    public function create(array $data): DramaEpisode
    {
        $data = $this->onlyFields($data);
        $this->assertUnique($data, null);
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): void
    {
        $current = $this->find($id);
        $data = $this->onlyFields($data);
        $this->assertUnique([
            'drama_id' => $data['drama_id'] ?? $current->drama_id,
            'episode_no' => $data['episode_no'] ?? $current->episode_no,
            'external_video_id' => $data['external_video_id'] ?? $current->external_video_id,
        ], $id);
        $this->repository->updateById($id, $data);
    }

    public function batchStatus(array $ids, int $status): void
    {
        if (! in_array($status, [0, 1], true)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '分集状态不合法');
        }
        $this->repository->batchUpdateStatus($ids, $status);
    }

    private function assertUnique(array $data, ?int $idExcept): void
    {
        if ($this->repository->existsEpisodeNumber((int) $data['drama_id'], (int) $data['episode_no'], $idExcept)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '同一短剧的 episode_no 已存在');
        }
        if ($this->repository->existsExternalVideoId((string) $data['external_video_id'], $idExcept)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'external_video_id 已存在');
        }
    }

    private function onlyFields(array $data): array
    {
        return array_intersect_key($data, array_flip(self::FIELDS));
    }
}
