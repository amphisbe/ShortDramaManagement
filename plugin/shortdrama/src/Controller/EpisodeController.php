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
use Plugin\ShortDrama\Request\BatchEpisodeStatusRequest;
use Plugin\ShortDrama\Request\EpisodeRequest;
use Plugin\ShortDrama\Service\EpisodeService;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class EpisodeController extends AbstractController
{
    public function __construct(private readonly EpisodeService $service) {}

    #[Get(path: '/admin/shortdrama/episodes', summary: '分集列表', tags: ['分集管理'])]
    #[Permission(code: 'shortdrama:episode:view')]
    public function list(): Result
    {
        return $this->success($this->service->page(
            $this->getRequestData(),
            $this->getCurrentPage(),
            $this->getPageSize()
        ));
    }

    #[Post(path: '/admin/shortdrama/episodes', summary: '创建分集', tags: ['分集管理'])]
    #[Permission(code: 'shortdrama:episode:update')]
    public function create(EpisodeRequest $request): Result
    {
        return $this->success($this->service->create($request->validated()));
    }

    #[Put(path: '/admin/shortdrama/episodes/{id}', summary: '更新分集', tags: ['分集管理'])]
    #[Permission(code: 'shortdrama:episode:update')]
    public function update(int $id, EpisodeRequest $request): Result
    {
        $this->service->update($id, $request->validated());
        return $this->success();
    }

    #[Post(path: '/admin/shortdrama/episodes/batch-status', summary: '批量更新分集状态', tags: ['分集管理'])]
    #[Permission(code: 'shortdrama:episode:update')]
    public function batchStatus(BatchEpisodeStatusRequest $request): Result
    {
        $data = $request->validated();
        $this->service->batchStatus(Arr::get($data, 'ids', []), (int) Arr::get($data, 'status'));
        return $this->success();
    }
}
