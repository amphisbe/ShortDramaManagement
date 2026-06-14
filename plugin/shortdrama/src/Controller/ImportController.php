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
use Mine\Access\Attribute\Permission;
use Plugin\ShortDrama\Request\ImportRequest;
use Plugin\ShortDrama\Service\ImportService;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class ImportController extends AbstractController
{
    public function __construct(private readonly ImportService $service) {}

    #[Post(path: '/admin/shortdrama/imports/validate', summary: '校验 Excel 数据', tags: ['短剧数据导入'])]
    #[Permission(code: 'shortdrama:import:execute')]
    public function validate(ImportRequest $request): Result
    {
        return $this->success($this->withUploadedFile($request, fn (string $path, string $type): array => $this->service->validate($path, $type)));
    }

    #[Post(path: '/admin/shortdrama/imports/execute', summary: '执行 Excel 部分成功导入', tags: ['短剧数据导入'])]
    #[Permission(code: 'shortdrama:import:execute')]
    public function execute(ImportRequest $request): Result
    {
        return $this->success($this->withUploadedFile($request, fn (string $path, string $type): array => $this->service->execute($path, $type)));
    }

    #[Get(path: '/admin/shortdrama/imports/{id}/report', summary: '获取 Excel 导入错误报告', tags: ['短剧数据导入'])]
    #[Permission(code: 'shortdrama:import:execute')]
    public function report(string $id): Result
    {
        return $this->success($this->service->report($id));
    }

    private function withUploadedFile(ImportRequest $request, callable $callback): array
    {
        $file = $request->file('file');
        $path = sys_get_temp_dir() . '/shortdrama-import-' . bin2hex(random_bytes(8)) . '.' . $file->getExtension();
        $file->moveTo($path);
        try {
            return $callback($path, (string) Arr::get($request->validated(), 'type'));
        } finally {
            is_file($path) && unlink($path);
        }
    }
}
