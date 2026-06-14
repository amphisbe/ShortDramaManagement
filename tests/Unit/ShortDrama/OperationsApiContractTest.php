<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Mockery;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Contract\AppUserRepositoryInterface;
use Plugin\ShortDrama\Contract\DashboardRepositoryInterface;
use Plugin\ShortDrama\Controller\AppUserController;
use Plugin\ShortDrama\Controller\DashboardController;
use Plugin\ShortDrama\Controller\ImportController;
use Plugin\ShortDrama\Model\AppUser;
use Plugin\ShortDrama\Service\AppUserService;
use Plugin\ShortDrama\Service\DashboardService;
use ReflectionClass;

final class OperationsApiContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testAppUserServiceOnlyUpdatesStatus(): void
    {
        $user = new AppUser(['external_user_id' => 'U1', 'status' => 1]);
        $repository = Mockery::mock(AppUserRepositoryInterface::class);
        $repository->shouldReceive('findById')->with(7)->once()->andReturn($user);
        $repository->shouldReceive('updateStatus')->with(7, 0)->once()->andReturnTrue();

        (new AppUserService($repository))->updateStatus(7, 0);
        self::assertTrue(true);
    }

    public function testAppUserServiceRejectsInvalidStatusAndMissingUser(): void
    {
        $repository = Mockery::mock(AppUserRepositoryInterface::class);
        $service = new AppUserService($repository);

        $this->assertBusinessException(fn () => $service->updateStatus(7, 2), ResultCode::UNPROCESSABLE_ENTITY);

        $repository->shouldReceive('findById')->with(404)->once()->andReturnNull();
        $this->assertBusinessException(fn () => $service->updateStatus(404, 0), ResultCode::NOT_FOUND);
    }

    public function testDashboardServiceReturnsOnlyRealtimeSections(): void
    {
        $repository = Mockery::mock(DashboardRepositoryInterface::class);
        $repository->shouldReceive('overview')->once()->andReturn(['dramas' => 2]);
        $repository->shouldReceive('ranking')->once()->andReturn([['title' => 'A']]);
        $repository->shouldReceive('distribution')->once()->andReturn(['status' => []]);
        $service = new DashboardService($repository);

        self::assertSame(['dramas' => 2], $service->overview());
        self::assertSame([['title' => 'A']], $service->ranking());
        self::assertSame(['status' => []], $service->distribution());
        self::assertFalse(method_exists($service, 'trend'));
    }

    public function testOperationsControllersDeclareMvpRoutesAndPermissions(): void
    {
        $this->assertRoute(DashboardController::class, 'overview', Get::class, '/admin/shortdrama/dashboard/overview', 'shortdrama:dashboard:view');
        $this->assertRoute(DashboardController::class, 'ranking', Get::class, '/admin/shortdrama/dashboard/ranking', 'shortdrama:dashboard:view');
        $this->assertRoute(DashboardController::class, 'distribution', Get::class, '/admin/shortdrama/dashboard/distribution', 'shortdrama:dashboard:view');
        $this->assertRoute(AppUserController::class, 'list', Get::class, '/admin/shortdrama/users', 'shortdrama:user:view');
        $this->assertRoute(AppUserController::class, 'detail', Get::class, '/admin/shortdrama/users/{id}', 'shortdrama:user:view');
        $this->assertRoute(AppUserController::class, 'updateStatus', Put::class, '/admin/shortdrama/users/{id}/status', 'shortdrama:user:update');
        $this->assertRoute(ImportController::class, 'validate', Post::class, '/admin/shortdrama/imports/validate', 'shortdrama:import:execute');
        $this->assertRoute(ImportController::class, 'execute', Post::class, '/admin/shortdrama/imports/execute', 'shortdrama:import:execute');
        $this->assertRoute(ImportController::class, 'report', Get::class, '/admin/shortdrama/imports/{id}/report', 'shortdrama:import:execute');

        foreach ([DashboardController::class, AppUserController::class, ImportController::class] as $controller) {
            self::assertCount(3, (new ReflectionClass($controller))->getAttributes(Middleware::class));
        }
    }

    private function assertRoute(string $class, string $method, string $routeClass, string $path, string $permission): void
    {
        $reflection = (new ReflectionClass($class))->getMethod($method);
        self::assertSame($path, $reflection->getAttributes($routeClass)[0]->newInstance()->path);
        self::assertSame([$permission], $reflection->getAttributes(Permission::class)[0]->newInstance()->getCode());
    }

    private function assertBusinessException(callable $callback, ResultCode $code): void
    {
        try {
            $callback();
            self::fail('Expected business exception.');
        } catch (BusinessException $exception) {
            self::assertSame($code, $exception->getResponse()->code);
        }
    }
}
