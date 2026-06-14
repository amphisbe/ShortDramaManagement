<?php

declare(strict_types=1);

namespace HyperfTests\Feature\ShortDrama;

use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Installer\InstallStoreInterface;
use Plugin\ShortDrama\Installer\ShortDramaInstallDefinition;
use Plugin\ShortDrama\Installer\ShortDramaInstaller;

final class InstallScriptTest extends TestCase
{
    public function testMenuTreeUsesApprovedChineseTitles(): void
    {
        $tree = ShortDramaInstallDefinition::menuTree();

        self::assertSame('短剧运营', $tree['meta']['title']);
        self::assertSame(
            ['数据看板', '短剧管理', '分集管理', '批量上传', 'App 用户', '数据导入'],
            array_column(array_column($tree['children'], 'meta'), 'title')
        );
    }

    public function testTargetRolesAreSuperAdminAndOperations(): void
    {
        self::assertSame([
            'SuperAdmin' => '超级管理员',
            'operations' => '运营',
        ], ShortDramaInstallDefinition::roles());
    }

    public function testOperationsOnlyReceivesShortDramaPermissions(): void
    {
        $permissions = ShortDramaInstallDefinition::operationsPermissions();

        self::assertContains('shortdrama:dashboard:view', $permissions);
        self::assertContains('shortdrama:drama:create', $permissions);
        self::assertContains('shortdrama:episode:update', $permissions);
        self::assertContains('shortdrama:media:upload', $permissions);
        self::assertContains('shortdrama:user:update', $permissions);
        self::assertContains('shortdrama:import:execute', $permissions);
        self::assertSame([], array_filter(
            $permissions,
            static fn (string $permission): bool => ! str_starts_with($permission, 'shortdrama:')
        ));
    }

    public function testInstallerIsIdempotentAndKeepsOperationsInsideBusinessMenus(): void
    {
        $store = new InMemoryInstallStore();
        $installer = new ShortDramaInstaller($store);

        $installer->install();
        $installer->install();

        self::assertCount(18, $store->menus);
        self::assertSame(['SuperAdmin', 'operations'], array_keys($store->roles));
        self::assertCount(18, $store->roleMenus['SuperAdmin']);
        self::assertCount(17, $store->roleMenus['operations']);
        self::assertNotContains(1, $store->roleMenus['operations']);
    }
}

final class InMemoryInstallStore implements InstallStoreInterface
{
    public array $menus = [
        'permission' => ['id' => 1, 'parent_id' => 0, 'name' => 'permission'],
    ];

    public array $roles = [];

    public array $roleMenus = [];

    public function transaction(callable $callback): void
    {
        $callback();
    }

    public function upsertMenu(string $name, int $parentId, array $attributes): int
    {
        $id = $this->menus[$name]['id'] ?? count($this->menus) + 1;
        $this->menus[$name] = ['id' => $id, 'parent_id' => $parentId, ...$attributes];

        return $id;
    }

    public function upsertRole(string $code, array $attributes): void
    {
        $this->roles[$code] = $attributes;
    }

    public function allMenuIds(): array
    {
        return array_column($this->menus, 'id');
    }

    public function syncRoleMenus(string $roleCode, array $menuIds): void
    {
        sort($menuIds);
        $this->roleMenus[$roleCode] = $menuIds;
    }
}
