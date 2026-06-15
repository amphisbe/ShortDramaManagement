<?php

declare(strict_types=1);

namespace HyperfTests\Feature\ShortDrama;

use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Mine\Access\Attribute\Permission;
use Mockery;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Contract\EpisodeRepositoryInterface;
use Plugin\ShortDrama\Contract\MediaAssetRepositoryInterface;
use Plugin\ShortDrama\Controller\MediaController;
use Plugin\ShortDrama\Model\Drama;
use Plugin\ShortDrama\Request\MediaCheckRequest;
use Plugin\ShortDrama\Service\MediaValidationService;
use ReflectionClass;

final class MediaCheckControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testControllerDeclaresProtectedBatchCheckRoute(): void
    {
        self::assertTrue(class_exists(MediaController::class));

        $class = new ReflectionClass(MediaController::class);
        self::assertSame('http', $class->getAttributes(HyperfServer::class)[0]->newInstance()->name);
        $middleware = array_map(
            static fn ($attribute) => $attribute->newInstance(),
            $class->getAttributes(Middleware::class),
        );
        self::assertSame(
            [[AccessTokenMiddleware::class, 100], [PermissionMiddleware::class, 99], [OperationMiddleware::class, 98]],
            array_map(static fn (Middleware $item) => [$item->middleware, $item->priority], $middleware),
        );

        $method = $class->getMethod('check');
        self::assertSame('/admin/shortdrama/media/check', $method->getAttributes(Post::class)[0]->newInstance()->path);
        self::assertSame(['shortdrama:media:upload'], $method->getAttributes(Permission::class)[0]->newInstance()->getCode());
    }

    public function testRequestValidatesEveryFileDescriptor(): void
    {
        self::assertTrue(class_exists(MediaCheckRequest::class));

        $rules = (new ReflectionClass(MediaCheckRequest::class))->newInstanceWithoutConstructor()->rules();

        self::assertSame('required|array|min:1|max:200', $rules['files']);
        self::assertSame('required|string|max:255', $rules['files.*.name']);
        self::assertSame('required|integer|min:1', $rules['files.*.size']);
        self::assertSame('required|string|in:video/mp4,application/mp4', $rules['files.*.mime_type']);
        self::assertSame('required|string|size:64|regex:/^[a-f0-9]{64}$/i', $rules['files.*.sha256']);
    }

    public function testBatchCheckKeepsAcceptedFilesWhenOtherFilesFail(): void
    {
        self::assertTrue(class_exists(MediaValidationService::class));

        $dramas = Mockery::mock(DramaRepositoryInterface::class);
        $dramas->shouldReceive('findByExternalId')->andReturnUsing(function (string $id): ?Drama {
            if ($id === 'MISSING') {
                return null;
            }
            $drama = new Drama(['external_drama_id' => $id]);
            $drama->setAttribute('id', 7);
            return $drama;
        });

        $episodes = Mockery::mock(EpisodeRepositoryInterface::class);
        $episodes->shouldReceive('existsEpisodeNumber')->andReturnUsing(
            static fn (int $dramaId, int $episodeNo): bool => $episodeNo === 3,
        );
        $episodes->shouldReceive('existsExternalVideoId')->andReturnUsing(
            static fn (string $videoId): bool => $videoId === 'DRAMA001_ep04',
        );

        $assets = Mockery::mock(MediaAssetRepositoryInterface::class);
        $assets->shouldReceive('existsObjectKey')->andReturnFalse();
        $assets->shouldReceive('existsSha256')->andReturnUsing(
            static fn (string $sha256): bool => $sha256 === str_repeat('5', 64),
        );

        $files = [
            $this->file('bad-name.mp4', '1'),
            $this->file('MISSING_ep01.mp4', '2'),
            $this->file('DRAMA001_ep03.mp4', '3'),
            $this->file('DRAMA001_ep04.mp4', '4'),
            $this->file('DRAMA001_ep05.mp4', '5'),
            $this->file('DRAMA001_ep06.mp4', '6'),
            $this->file('DRAMA001_ep07.mp4', '6'),
        ];

        $result = (new MediaValidationService($dramas, $episodes, $assets))->check($files);

        self::assertSame([
            'INVALID_FILENAME',
            'DRAMA_NOT_FOUND',
            'EPISODE_EXISTS',
            'VIDEO_ID_EXISTS',
            'HASH_EXISTS',
            null,
            'DUPLICATE_IN_BATCH',
        ], array_column($result, 'code'));
        self::assertTrue($result[5]['accepted']);
        self::assertSame('DRAMA001_ep06', $result[5]['external_video_id']);
        self::assertSame('videos/DRAMA001/DRAMA001_ep06.mp4', $result[5]['object_key']);
        self::assertFalse($result[6]['accepted']);
    }

    private function file(string $name, string $hashDigit): array
    {
        return [
            'name' => $name,
            'size' => 1024,
            'mime_type' => 'video/mp4',
            'sha256' => str_repeat($hashDigit, 64),
        ];
    }
}
