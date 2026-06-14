<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Controller;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Mine\Access\Attribute\Permission;
use Plugin\ShortDrama\Service\DashboardService;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $service) {}

    #[Get(path: '/admin/shortdrama/dashboard/overview', summary: '实时核心指标', tags: ['短剧数据看板'])]
    #[Permission(code: 'shortdrama:dashboard:view')]
    public function overview(): Result
    {
        return $this->success($this->service->overview());
    }

    #[Get(path: '/admin/shortdrama/dashboard/ranking', summary: '短剧播放排行', tags: ['短剧数据看板'])]
    #[Permission(code: 'shortdrama:dashboard:view')]
    public function ranking(): Result
    {
        return $this->success($this->service->ranking());
    }

    #[Get(path: '/admin/shortdrama/dashboard/distribution', summary: '短剧状态和分类分布', tags: ['短剧数据看板'])]
    #[Permission(code: 'shortdrama:dashboard:view')]
    public function distribution(): Result
    {
        return $this->success($this->service->distribution());
    }
}
