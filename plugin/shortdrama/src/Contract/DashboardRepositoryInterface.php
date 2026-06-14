<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Contract;

interface DashboardRepositoryInterface
{
    public function overview(): array;

    public function ranking(): array;

    public function distribution(): array;
}
