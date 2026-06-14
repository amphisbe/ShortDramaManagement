<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Controller;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use Hyperf\Collection\Arr;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Plugin\ShortDrama\Request\AppUserStatusRequest;
use Plugin\ShortDrama\Service\AppUserService;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class AppUserController extends AbstractController
{
    public function __construct(private readonly AppUserService $service) {}

    #[Get(path: '/admin/shortdrama/users', summary: 'App 用户列表', tags: ['App 用户管理'])]
    #[Permission(code: 'shortdrama:user:view')]
    public function list(): Result
    {
        return $this->success($this->service->page(
            $this->getRequestData(),
            $this->getCurrentPage(),
            $this->getPageSize()
        ));
    }

    #[Get(path: '/admin/shortdrama/users/{id}', summary: 'App 用户只读详情', tags: ['App 用户管理'])]
    #[Permission(code: 'shortdrama:user:view')]
    public function detail(int $id): Result
    {
        return $this->success($this->service->find($id));
    }

    #[Put(path: '/admin/shortdrama/users/{id}/status', summary: '禁用或恢复 App 用户', tags: ['App 用户管理'])]
    #[Permission(code: 'shortdrama:user:update')]
    public function updateStatus(int $id, AppUserStatusRequest $request): Result
    {
        $this->service->updateStatus($id, (int) Arr::get($request->validated(), 'status'));
        return $this->success();
    }
}
