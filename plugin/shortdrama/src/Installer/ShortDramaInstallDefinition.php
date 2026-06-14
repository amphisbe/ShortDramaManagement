<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Installer;

final class ShortDramaInstallDefinition
{
    public static function roles(): array
    {
        return [
            'SuperAdmin' => '超级管理员',
            'operations' => '运营',
        ];
    }

    public static function operationsPermissions(): array
    {
        return [
            'shortdrama:dashboard:view',
            'shortdrama:drama:view',
            'shortdrama:drama:create',
            'shortdrama:drama:update',
            'shortdrama:episode:view',
            'shortdrama:episode:update',
            'shortdrama:media:upload',
            'shortdrama:user:view',
            'shortdrama:user:update',
            'shortdrama:import:execute',
        ];
    }

    public static function menuTree(): array
    {
        return self::menu(
            name: 'shortdrama',
            title: '短剧运营',
            path: '/shortdrama',
            icon: 'ri:movie-2-line',
            children: [
                self::page(
                    name: 'shortdrama-dashboard',
                    title: '数据看板',
                    path: '/shortdrama/dashboard',
                    component: 'shortdrama/admin/views/dashboard/index',
                    icon: 'ri:dashboard-line',
                    permissions: ['shortdrama:dashboard:view' => '查看数据看板']
                ),
                self::page(
                    name: 'shortdrama-drama',
                    title: '短剧管理',
                    path: '/shortdrama/drama',
                    component: 'shortdrama/admin/views/drama/index',
                    icon: 'ri:film-line',
                    permissions: [
                        'shortdrama:drama:view' => '查看短剧',
                        'shortdrama:drama:create' => '新增短剧',
                        'shortdrama:drama:update' => '编辑短剧',
                    ]
                ),
                self::page(
                    name: 'shortdrama-episode',
                    title: '分集管理',
                    path: '/shortdrama/episode',
                    component: 'shortdrama/admin/views/episode/index',
                    icon: 'ri:play-list-2-line',
                    permissions: [
                        'shortdrama:episode:view' => '查看分集',
                        'shortdrama:episode:update' => '编辑分集',
                    ]
                ),
                self::page(
                    name: 'shortdrama-upload',
                    title: '批量上传',
                    path: '/shortdrama/upload',
                    component: 'shortdrama/admin/views/upload/index',
                    icon: 'ri:upload-cloud-2-line',
                    permissions: ['shortdrama:media:upload' => '上传视频']
                ),
                self::page(
                    name: 'shortdrama-user',
                    title: 'App 用户',
                    path: '/shortdrama/user',
                    component: 'shortdrama/admin/views/user/index',
                    icon: 'ri:user-3-line',
                    permissions: [
                        'shortdrama:user:view' => '查看 App 用户',
                        'shortdrama:user:update' => '修改用户状态',
                    ]
                ),
                self::page(
                    name: 'shortdrama-import',
                    title: '数据导入',
                    path: '/shortdrama/import',
                    component: 'shortdrama/admin/views/import/index',
                    icon: 'ri:file-excel-2-line',
                    permissions: ['shortdrama:import:execute' => '执行数据导入']
                ),
            ]
        );
    }

    private static function page(
        string $name,
        string $title,
        string $path,
        string $component,
        string $icon,
        array $permissions
    ): array {
        $children = [];
        foreach ($permissions as $permission => $permissionTitle) {
            $children[] = self::button($permission, $permissionTitle);
        }

        return self::menu($name, $title, $path, $component, $icon, $children);
    }

    private static function menu(
        string $name,
        string $title,
        string $path = '',
        string $component = '',
        string $icon = '',
        array $children = []
    ): array {
        return [
            'name' => $name,
            'path' => $path,
            'component' => $component,
            'redirect' => '',
            'status' => 1,
            'sort' => 20,
            'remark' => '',
            'meta' => [
                'title' => $title,
                'i18n' => $title,
                'icon' => $icon,
                'type' => 'M',
                'hidden' => false,
                'componentPath' => 'plugins/',
                'componentSuffix' => '.vue',
                'breadcrumbEnable' => true,
                'copyright' => true,
                'cache' => true,
                'affix' => false,
            ],
            'children' => $children,
        ];
    }

    private static function button(string $name, string $title): array
    {
        return [
            'name' => $name,
            'path' => '',
            'component' => '',
            'redirect' => '',
            'status' => 1,
            'sort' => 0,
            'remark' => '',
            'meta' => [
                'title' => $title,
                'i18n' => $title,
                'type' => 'B',
            ],
            'children' => [],
        ];
    }
}
