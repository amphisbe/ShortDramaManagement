<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Service;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Contract\DramaWriterInterface;
use Plugin\ShortDrama\Model\Drama;

final class DramaService implements DramaWriterInterface
{
    private const FIELDS = [
        'external_drama_id', 'title', 'display_author_name', 'author_user_id',
        'total_episodes', 'cover_url', 'vip_free', 'status', 'description',
        'category', 'tags',
    ];

    public function __construct(private readonly DramaRepositoryInterface $repository) {}

    public function page(array $params, ?int $page = null, ?int $pageSize = null): array
    {
        return $this->repository->page($params, $page, $pageSize);
    }

    public function find(int $id): Drama
    {
        return $this->repository->findById($id)
            ?? throw new BusinessException(ResultCode::NOT_FOUND);
    }

    public function create(array $data): Drama
    {
        $data = $this->onlyFields($data);
        if ($this->repository->existsExternalId($data['external_drama_id'])) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'external_drama_id 已存在');
        }
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): void
    {
        $current = $this->find($id);
        $data = $this->onlyFields($data);
        $externalId = $data['external_drama_id'] ?? $current->external_drama_id;
        if ($this->repository->existsExternalId((string) $externalId, $id)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, 'external_drama_id 已存在');
        }
        $this->repository->updateById($id, $data);
    }

    public function batchStatus(array $ids, int $status): void
    {
        if (! in_array($status, [0, 1, 2], true)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '短剧状态不合法');
        }
        $this->repository->batchUpdateStatus($ids, $status);
    }

    private function onlyFields(array $data): array
    {
        return array_intersect_key($data, array_flip(self::FIELDS));
    }
}
