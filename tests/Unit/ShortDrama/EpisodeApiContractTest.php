<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use Hyperf\Swagger\Annotation\Delete;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Mockery;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Contract\EpisodeRepositoryInterface;
use Plugin\ShortDrama\Controller\EpisodeController;
use Plugin\ShortDrama\Model\DramaEpisode;
use Plugin\ShortDrama\Request\BatchEpisodeStatusRequest;
use Plugin\ShortDrama\Request\EpisodeRequest;
use Plugin\ShortDrama\Repository\EpisodeRepository;
use Plugin\ShortDrama\Service\EpisodeService;
use ReflectionClass;

final class EpisodeApiContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testEpisodeControllerDeclaresExactRoutesAndPermissionsWithoutDelete(): void
    {
        $class = new ReflectionClass(EpisodeController::class);
        $this->assertRoute($class, 'list', Get::class, '/admin/shortdrama/episodes', 'shortdrama:episode:view');
        $this->assertRoute($class, 'create', Post::class, '/admin/shortdrama/episodes', 'shortdrama:episode:update');
        $this->assertRoute($class, 'update', Put::class, '/admin/shortdrama/episodes/{id}', 'shortdrama:episode:update');
        $this->assertRoute($class, 'batchStatus', Post::class, '/admin/shortdrama/episodes/batch-status', 'shortdrama:episode:update');
        foreach ($class->getMethods() as $method) {
            self::assertSame([], $method->getAttributes(Delete::class));
            self::assertNotSame('delete', strtolower($method->getName()));
        }
    }

    public function testEpisodeRequestUsesRequiredForPostAndSometimesForPut(): void
    {
        $post = $this->episodeRequest(true)->rules();
        $put = $this->episodeRequest(false)->rules();
        self::assertSame('required|integer|min:1', $post['drama_id']);
        self::assertSame('sometimes|integer|min:1', $put['drama_id']);
        self::assertSame('required|string|max:24', $post['external_video_id']);
        self::assertSame('required|integer|in:0,1', $post['loop']);
        self::assertSame('required|string', $post['tool_info_json']);
        self::assertSame('required|integer|in:0,1', (new ReflectionClass(BatchEpisodeStatusRequest::class))->newInstanceWithoutConstructor()->rules()['status']);
    }

    public function testEpisodeServiceRejectsDuplicateEpisodeNumber(): void
    {
        $repository = Mockery::mock(EpisodeRepositoryInterface::class);
        $repository->shouldReceive('existsEpisodeNumber')->with(3, 2, null)->once()->andReturnTrue();
        $repository->shouldNotReceive('create');

        $this->assertBusinessException(
            fn () => (new EpisodeService($repository))->create($this->episodeData()),
            ResultCode::UNPROCESSABLE_ENTITY,
            '同一短剧的 episode_no 已存在'
        );
    }

    public function testEpisodeServiceRejectsDuplicateExternalVideoId(): void
    {
        $repository = Mockery::mock(EpisodeRepositoryInterface::class);
        $repository->shouldReceive('existsEpisodeNumber')->with(3, 2, null)->once()->andReturnFalse();
        $repository->shouldReceive('existsExternalVideoId')->with('video-2', null)->once()->andReturnTrue();
        $repository->shouldNotReceive('create');

        $this->assertBusinessException(
            fn () => (new EpisodeService($repository))->create($this->episodeData()),
            ResultCode::UNPROCESSABLE_ENTITY,
            'external_video_id 已存在'
        );
    }

    public function testEpisodeServiceThrowsNotFoundBeforeUpdate(): void
    {
        $repository = Mockery::mock(EpisodeRepositoryInterface::class);
        $repository->shouldReceive('findById')->with(44)->once()->andReturnNull();
        $repository->shouldNotReceive('updateById');

        $this->assertBusinessException(
            fn () => (new EpisodeService($repository))->update(44, ['title' => 'missing']),
            ResultCode::NOT_FOUND
        );
    }

    public function testEpisodeServiceCreatesWhitelistedDataAndDelegatesBatchStatus(): void
    {
        $data = $this->episodeData();
        $episode = new DramaEpisode($data);
        $repository = Mockery::mock(EpisodeRepositoryInterface::class);
        $repository->shouldReceive('existsEpisodeNumber')->with(3, 2, null)->once()->andReturnFalse();
        $repository->shouldReceive('existsExternalVideoId')->with('video-2', null)->once()->andReturnFalse();
        $repository->shouldReceive('create')->with($data)->once()->andReturn($episode);
        $repository->shouldReceive('batchUpdateStatus')->with([1, 2], 1)->once();
        $service = new EpisodeService($repository);

        self::assertSame($episode, $service->create($data + ['updated_by' => 9]));
        $service->batchStatus([1, 2], 1);
    }

    public function testEpisodeBatchRepositoryUsesDramaTransactionAndChecksAllIdsFirst(): void
    {
        $source = file_get_contents((new ReflectionClass(EpisodeRepository::class))->getFileName());
        self::assertStringContainsString("Db::connection('drama')->transaction", $source);
        self::assertLessThan(strpos($source, 'updateStatusByIds($ids, $status)'), strpos($source, 'countByIds($ids)'));
    }

    private function assertRoute(ReflectionClass $class, string $method, string $routeClass, string $path, string $permission): void
    {
        $reflection = $class->getMethod($method);
        self::assertSame($path, $reflection->getAttributes($routeClass)[0]->newInstance()->path);
        self::assertSame([$permission], $reflection->getAttributes(Permission::class)[0]->newInstance()->getCode());
    }

    private function episodeRequest(bool $post): EpisodeRequest
    {
        return new class($post) extends EpisodeRequest {
            public function __construct(private readonly bool $post) {}

            public function isMethod(string $method): bool
            {
                return $this->post && strtolower($method) === 'post';
            }
        };
    }

    private function episodeData(): array
    {
        return ['drama_id' => 3, 'episode_no' => 2, 'external_video_id' => 'video-2'];
    }

    private function assertBusinessException(callable $callback, ResultCode $code, ?string $message = null): void
    {
        try {
            $callback();
            self::fail('Expected business exception.');
        } catch (BusinessException $exception) {
            self::assertSame($code, $exception->getResponse()->code);
            if ($message !== null) {
                self::assertSame($message, $exception->getResponse()->message);
            }
        }
    }
}
