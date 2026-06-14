<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Installer;

final class ShortDramaInstaller
{
    public function __construct(private readonly InstallStoreInterface $store)
    {
    }

    public function install(): void
    {
        $this->store->transaction(function (): void {
            $shortDramaMenuIds = [];
            $this->installMenu(ShortDramaInstallDefinition::menuTree(), 0, $shortDramaMenuIds);

            foreach (ShortDramaInstallDefinition::roles() as $code => $name) {
                $this->store->upsertRole($code, [
                    'name' => $name,
                    'status' => 1,
                    'sort' => $code === 'SuperAdmin' ? 0 : 10,
                    'remark' => $code === 'SuperAdmin' ? '系统超级管理员' : '短剧后台运营人员',
                ]);
            }

            $this->store->syncRoleMenus('SuperAdmin', $this->store->allMenuIds());
            $this->store->syncRoleMenus('operations', $shortDramaMenuIds);
        });
    }

    private function installMenu(array $definition, int $parentId, array &$menuIds): void
    {
        $children = $definition['children'];
        unset($definition['children']);

        $name = $definition['name'];
        unset($definition['name']);

        $menuId = $this->store->upsertMenu($name, $parentId, $definition);
        $menuIds[] = $menuId;

        foreach ($children as $child) {
            $this->installMenu($child, $menuId, $menuIds);
        }
    }
}
