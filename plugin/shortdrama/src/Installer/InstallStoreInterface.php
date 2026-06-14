<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Installer;

interface InstallStoreInterface
{
    public function transaction(callable $callback): void;

    public function upsertMenu(string $name, int $parentId, array $attributes): int;

    public function upsertRole(string $code, array $attributes): void;

    public function allMenuIds(): array;

    public function syncRoleMenus(string $roleCode, array $menuIds): void;
}
