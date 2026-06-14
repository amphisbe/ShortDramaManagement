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
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Plugin\ShortDrama\Request\BatchDramaStatusRequest;
use Plugin\ShortDrama\Request\DramaRequest;
use Plugin\ShortDrama\Service\DramaService;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class DramaController extends AbstractController
{
    public function __construct(private readonly DramaService $service) {}

    #[Get(path: '/admin/shortdrama/dramas', summary: '短剧列表', tags: ['短剧管理'])]
    #[Permission(code: 'shortdrama:drama:view')]
    public function list(): Result
    {
        return $this->success($this->service->page(
            $this->getRequestData(),
            $this->getCurrentPage(),
            $this->getPageSize()
        ));
    }

    #[Post(path: '/admin/shortdrama/dramas', summary: '创建短剧', tags: ['短剧管理'])]
    #[Permission(code: 'shortdrama:drama:create')]
    public function create(DramaRequest $request): Result
    {
        return $this->success($this->service->create($request->validated()));
    }

    #[Put(path: '/admin/shortdrama/dramas/{id}', summary: '更新短剧', tags: ['短剧管理'])]
    #[Permission(code: 'shortdrama:drama:update')]
    public function update(int $id, DramaRequest $request): Result
    {
        $this->service->update($id, $request->validated());
        return $this->success();
    }

    #[Post(path: '/admin/shortdrama/dramas/batch-status', summary: '批量更新短剧状态', tags: ['短剧管理'])]
    #[Permission(code: 'shortdrama:drama:update')]
    public function batchStatus(BatchDramaStatusRequest $request): Result
    {
        $data = $request->validated();
        $this->service->batchStatus(Arr::get($data, 'ids', []), (int) Arr::get($data, 'status'));
        return $this->success();
    }
}
