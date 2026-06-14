<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Contract;

use Plugin\ShortDrama\Model\Drama;

interface DramaRepositoryInterface
{
    public function page(array $params, ?int $page = null, ?int $pageSize = null): array;

    public function findById(mixed $id): ?Drama;

    public function create(array $data): Drama;

    public function updateById(mixed $id, array $data): bool;

    public function existsExternalId(string $externalId, ?int $idExcept = null): bool;

    public function countByIds(array $ids): int;

    public function updateStatusByIds(array $ids, int $status): int;

    public function batchUpdateStatus(array $ids, int $status): void;
}
