<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Installer;

use App\Model\Permission\Menu;
use App\Model\Permission\Meta;
use App\Model\Permission\Role;
use Hyperf\DbConnection\Db;
use RuntimeException;

final class ModelInstallStore implements InstallStoreInterface
{
    public function transaction(callable $callback): void
    {
        Db::transaction($callback);
    }

    public function upsertMenu(string $name, int $parentId, array $attributes): int
    {
        $menu = Menu::query()->where('name', $name)->first() ?? new Menu();
        $menu->fill([
            'name' => $name,
            'parent_id' => $parentId,
            'created_by' => 0,
            'updated_by' => 0,
            ...$attributes,
            'meta' => new Meta($attributes['meta']),
        ]);
        $menu->save();

        return (int) $menu->id;
    }

    public function upsertRole(string $code, array $attributes): void
    {
        $role = Role::query()->where('code', $code)->first() ?? new Role();
        $role->fill([
            'code' => $code,
            'created_by' => 0,
            'updated_by' => 0,
            ...$attributes,
        ]);
        $role->save();
    }

    public function allMenuIds(): array
    {
        return Menu::query()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    public function syncRoleMenus(string $roleCode, array $menuIds): void
    {
        $role = Role::query()->where('code', $roleCode)->first();
        if (! $role instanceof Role) {
            throw new RuntimeException(sprintf('Role [%s] was not installed.', $roleCode));
        }

        $role->menus()->sync(array_values(array_unique($menuIds)));
    }
}
