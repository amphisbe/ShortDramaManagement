<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Contract;

use Plugin\ShortDrama\Model\AppUser;

interface AppUserRepositoryInterface
{
    public function page(array $params, ?int $page = null, ?int $pageSize = null): array;

    public function findById(mixed $id): ?AppUser;

    public function updateStatus(int $id, int $status): bool;
}
