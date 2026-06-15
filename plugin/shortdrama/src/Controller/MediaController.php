<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Controller;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Http\CurrentUser;
use Hyperf\Collection\Arr;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Mine\Access\Attribute\Permission;
use Plugin\ShortDrama\Request\MediaCheckRequest;
use Plugin\ShortDrama\Request\ImagePresignRequest;
use Plugin\ShortDrama\Request\MediaCompleteRequest;
use Plugin\ShortDrama\Request\MediaPresignRequest;
use Plugin\ShortDrama\Service\MediaUploadService;
use Plugin\ShortDrama\Service\ImageUploadService;
use Plugin\ShortDrama\Service\MediaValidationService;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class MediaController extends AbstractController
{
    public function __construct(
        private readonly MediaValidationService $validation,
        private readonly MediaUploadService $upload,
        private readonly ImageUploadService $images,
        private readonly CurrentUser $currentUser,
    ) {
    }

    #[Post(path: '/admin/shortdrama/media/check', summary: '批量校验视频文件', tags: ['批量上传'])]
    #[Permission(code: 'shortdrama:media:upload')]
    public function check(MediaCheckRequest $request): Result
    {
        return $this->success($this->validation->check(Arr::get($request->validated(), 'files', [])));
    }

    #[Post(path: '/admin/shortdrama/media/presign', summary: '获取视频上传地址', tags: ['批量上传'])]
    #[Permission(code: 'shortdrama:media:upload')]
    public function presign(MediaPresignRequest $request): Result
    {
        return $this->success($this->upload->presign($request->validated(), $this->currentUser->id()));
    }

    #[Post(path: '/admin/shortdrama/media/complete', summary: '完成视频上传入库', tags: ['批量上传'])]
    #[Permission(code: 'shortdrama:media:upload')]
    public function complete(MediaCompleteRequest $request): Result
    {
        return $this->success($this->upload->complete((int) Arr::get($request->validated(), 'asset_id')));
    }

    #[Post(path: '/admin/shortdrama/images/upload/presign', summary: '获取公开图片上传地址', tags: ['批量上传'])]
    #[Permission(code: 'shortdrama:media:upload')]
    public function imagePresign(ImagePresignRequest $request): Result
    {
        return $this->success($this->images->presign($request->validated()));
    }
}
