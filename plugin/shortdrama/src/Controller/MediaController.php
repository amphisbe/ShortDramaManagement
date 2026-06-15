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
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Mine\Access\Attribute\Permission;
use Plugin\ShortDrama\Request\MediaCheckRequest;
use Plugin\ShortDrama\Service\MediaValidationService;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class MediaController extends AbstractController
{
    public function __construct(private readonly MediaValidationService $validation) {}

    #[Post(path: '/admin/shortdrama/media/check', summary: '批量校验视频文件', tags: ['批量上传'])]
    #[Permission(code: 'shortdrama:media:upload')]
    public function check(MediaCheckRequest $request): Result
    {
        return $this->success($this->validation->check(Arr::get($request->validated(), 'files', [])));
    }
}
