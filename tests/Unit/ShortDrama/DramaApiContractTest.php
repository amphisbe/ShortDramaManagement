<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use App\Exception\BusinessException;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\ResultCode;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\Delete;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Mockery;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Controller\DramaController;
use Plugin\ShortDrama\Model\Drama;
use Plugin\ShortDrama\Request\BatchDramaStatusRequest;
use Plugin\ShortDrama\Request\DramaRequest;
use Plugin\ShortDrama\Repository\DramaRepository;
use Plugin\ShortDrama\Service\DramaService;
use ReflectionClass;

final class DramaApiContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testDramaControllerDeclaresExactRoutesPermissionsAndMiddleware(): void
    {
        $class = new ReflectionClass(DramaController::class);
        self::assertSame('http', $class->getAttributes(HyperfServer::class)[0]->newInstance()->name);
        $middleware = array_map(
            static fn ($attribute) => $attribute->newInstance(),
            $class->getAttributes(Middleware::class)
        );
        self::assertSame(
            [[AccessTokenMiddleware::class, 100], [PermissionMiddleware::class, 99], [OperationMiddleware::class, 98]],
            array_map(static fn (Middleware $item) => [$item->middleware, $item->priority], $middleware)
        );

        $this->assertRoute($class, 'list', Get::class, '/admin/shortdrama/dramas', 'shortdrama:drama:view');
        $this->assertRoute($class, 'create', Post::class, '/admin/shortdrama/dramas', 'shortdrama:drama:create');
        $this->assertRoute($class, 'update', Put::class, '/admin/shortdrama/dramas/{id}', 'shortdrama:drama:update');
        $this->assertRoute($class, 'batchStatus', Post::class, '/admin/shortdrama/dramas/batch-status', 'shortdrama:drama:update');

        foreach ($class->getMethods() as $method) {
            self::assertSame([], $method->getAttributes(Delete::class));
            self::assertNotSame('delete', strtolower($method->getName()));
        }
    }

    public function testDramaRequestUsesRequiredForPostAndSometimesForPut(): void
    {
        $postRules = $this->dramaRequest(true)->rules();
        $putRules = $this->dramaRequest(false)->rules();

        self::assertSame('required|string|max:24', $postRules['external_drama_id']);
        self::assertSame('sometimes|string|max:24', $putRules['external_drama_id']);
        self::assertSame('required|integer|in:0,1,2', $postRules['status']);
        self::assertSame('nullable|string', $postRules['tags']);
        self::assertSame([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|min:1|distinct',
            'status' => 'required|integer|in:0,1,2',
        ], (new ReflectionClass(BatchDramaStatusRequest::class))->newInstanceWithoutConstructor()->rules());
    }

    public function testDramaServiceRejectsDuplicateExternalId(): void
    {
        $repository = Mockery::mock(DramaRepositoryInterface::class);
        $repository->shouldReceive('existsExternalId')->with('drama-1')->once()->andReturnTrue();
        $service = new DramaService($repository);

        try {
            $service->create(['external_drama_id' => 'drama-1']);
            self::fail('Expected duplicate external id exception.');
        } catch (BusinessException $exception) {
            self::assertSame(ResultCode::UNPROCESSABLE_ENTITY, $exception->getResponse()->code);
            self::assertSame('external_drama_id 已存在', $exception->getResponse()->message);
        }
    }

    public function testDramaServiceThrowsNotFoundBeforeUpdate(): void
    {
        $repository = Mockery::mock(DramaRepositoryInterface::class);
        $repository->shouldReceive('findById')->with(99)->once()->andReturnNull();
        $repository->shouldNotReceive('updateById');

        $this->expectException(BusinessException::class);
        try {
            (new DramaService($repository))->update(99, ['title' => 'missing']);
        } catch (BusinessException $exception) {
            self::assertSame(ResultCode::NOT_FOUND, $exception->getResponse()->code);
            throw $exception;
        }
    }

    public function testDramaServiceWhitelistsUpdateAndDelegatesBatchStatus(): void
    {
        $drama = new Drama(['external_drama_id' => 'drama-1']);
        $repository = Mockery::mock(DramaRepositoryInterface::class);
        $repository->shouldReceive('findById')->with(7)->once()->andReturn($drama);
        $repository->shouldReceive('existsExternalId')->with('drama-1', 7)->once()->andReturnFalse();
        $repository->shouldReceive('updateById')->with(7, ['title' => 'New'])->once()->andReturnTrue();
        $repository->shouldReceive('batchUpdateStatus')->with([7, 8], 2)->once();
        $service = new DramaService($repository);

        $service->update(7, ['title' => 'New', 'created_by' => 1]);
        $service->batchStatus([7, 8], 2);
        self::assertTrue(true);
    }

    public function testDramaBatchRepositoryUsesDramaTransactionAndChecksAllIdsFirst(): void
    {
        $source = file_get_contents((new ReflectionClass(DramaRepository::class))->getFileName());
        self::assertStringContainsString("Db::connection('drama')->transaction", $source);
        self::assertLessThan(strpos($source, 'updateStatusByIds($ids, $status)'), strpos($source, 'countByIds($ids)'));
    }

    private function assertRoute(ReflectionClass $class, string $method, string $routeClass, string $path, string $permission): void
    {
        $reflection = $class->getMethod($method);
        self::assertSame($path, $reflection->getAttributes($routeClass)[0]->newInstance()->path);
        self::assertSame([$permission], $reflection->getAttributes(Permission::class)[0]->newInstance()->getCode());
    }

    private function dramaRequest(bool $post): DramaRequest
    {
        return new class($post) extends DramaRequest {
            public function __construct(private readonly bool $post) {}

            public function isMethod(string $method): bool
            {
                return $this->post && strtolower($method) === 'post';
            }
        };
    }
}
