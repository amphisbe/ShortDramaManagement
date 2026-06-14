<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Service;

use Plugin\ShortDrama\Contract\DashboardRepositoryInterface;

final class DashboardService
{
    public function __construct(private readonly DashboardRepositoryInterface $repository) {}

    public function overview(): array
    {
        return $this->repository->overview();
    }

    public function ranking(): array
    {
        return $this->repository->ranking();
    }

    public function distribution(): array
    {
        return $this->repository->distribution();
    }
}
