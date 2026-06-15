<?php

declare(strict_types=1);

namespace HyperfTests\Feature\ShortDrama;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use Mockery;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Contract\EpisodeRepositoryInterface;
use Plugin\ShortDrama\Contract\MediaAssetRepositoryInterface;
use Plugin\ShortDrama\Contract\ObjectStorage;
use Plugin\ShortDrama\Controller\MediaController;
use Plugin\ShortDrama\Exception\MediaAssetReservationConflict;
use Plugin\ShortDrama\Infrastructure\R2StorageConfig;
use Plugin\ShortDrama\Model\Drama;
use Plugin\ShortDrama\Model\DramaEpisode;
use Plugin\ShortDrama\Model\MediaAsset;
use Plugin\ShortDrama\Request\MediaCompleteRequest;
use Plugin\ShortDrama\Request\MediaPresignRequest;
use Plugin\ShortDrama\Service\MediaUploadService;
use ReflectionClass;

final class MediaUploadControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testControllerDeclaresPresignAndCompleteRoutes(): void
    {
        $class = new ReflectionClass(MediaController::class);

        self::assertSame(
            '/admin/shortdrama/media/presign',
            $class->getMethod('presign')->getAttributes(\Hyperf\Swagger\Annotation\Post::class)[0]->newInstance()->path,
        );
        self::assertSame(
            '/admin/shortdrama/media/complete',
            $class->getMethod('complete')->getAttributes(\Hyperf\Swagger\Annotation\Post::class)[0]->newInstance()->path,
        );
    }

    public function testUploadRequestsValidateSingleFileAndAssetId(): void
    {
        self::assertTrue(class_exists(MediaPresignRequest::class));
        self::assertTrue(class_exists(MediaCompleteRequest::class));

        $presign = (new ReflectionClass(MediaPresignRequest::class))->newInstanceWithoutConstructor()->rules();
        $complete = (new ReflectionClass(MediaCompleteRequest::class))->newInstanceWithoutConstructor()->rules();

        self::assertSame('required|string|max:255', $presign['name']);
        self::assertSame('required|integer|min:1|max:524288000', $presign['size']);
        self::assertSame('required|string|in:video/mp4,application/mp4', $presign['mime_type']);
        self::assertSame('required|string|size:64|regex:/^[a-f0-9]{64}$/i', $presign['sha256']);
        self::assertSame(['asset_id' => 'required|integer|min:1'], $complete);
    }

    public function testPresignReservesAssetAndReturnsPrivatePutUrl(): void
    {
        self::assertTrue(class_exists(MediaUploadService::class));

        [$service, $dramas, $episodes, $assets, $storage] = $this->service();
        $dramas->shouldReceive('findByExternalId')->with('DRAMA001')->once()->andReturn($this->drama());
        $episodes->shouldReceive('existsEpisodeNumber')->with(7, 1)->once()->andReturnFalse();
        $episodes->shouldReceive('existsExternalVideoId')->with('DRAMA001_ep01')->once()->andReturnFalse();
        $asset = new MediaAsset(['object_key' => 'videos/DRAMA001/DRAMA001_ep01.mp4']);
        $asset->setAttribute('id', 12);
        $assets->shouldReceive('reserve')->once()->andReturn($asset);
        $storage->shouldReceive('presignPut')
            ->with('private-videos', 'videos/DRAMA001/DRAMA001_ep01.mp4', 'video/mp4', 900)
            ->once()
            ->andReturn('https://upload.example.test/signed');

        $result = $service->presign($this->file(), 9);

        self::assertSame(12, $result['asset_id']);
        self::assertSame('https://upload.example.test/signed', $result['upload_url']);
        self::assertSame('videos/DRAMA001/DRAMA001_ep01.mp4', $result['object_key']);
        self::assertSame(900, $result['expires_in']);
    }

    public function testActiveReservationBecomesConflictResponse(): void
    {
        self::assertTrue(class_exists(MediaUploadService::class));

        [$service, $dramas, $episodes, $assets] = $this->service();
        $dramas->shouldReceive('findByExternalId')->andReturn($this->drama());
        $episodes->shouldReceive('existsEpisodeNumber')->andReturnFalse();
        $episodes->shouldReceive('existsExternalVideoId')->andReturnFalse();
        $assets->shouldReceive('reserve')->andThrow(new MediaAssetReservationConflict(new MediaAsset()));

        try {
            $service->presign($this->file(), 9);
            self::fail('Expected reservation conflict.');
        } catch (BusinessException $exception) {
            self::assertSame(ResultCode::CONFLICT, $exception->getResponse()->code);
        }
    }

    public function testCompleteVerifiesObjectCreatesEpisodeAndMarksAssetUploaded(): void
    {
        self::assertTrue(class_exists(MediaUploadService::class));

        [$service, $dramas, $episodes, $assets, $storage] = $this->service();
        $asset = $this->pendingAsset();
        $assets->shouldReceive('transaction')->once()->andReturnUsing(static fn (callable $callback) => $callback());
        $assets->shouldReceive('findByIdForUpdate')->with(12)->once()->andReturn($asset);
        $storage->shouldReceive('head')->with('private-videos', $asset->object_key)->once()->andReturn([
            'content_length' => 1024,
            'content_type' => 'video/mp4',
            'etag' => 'etag',
        ]);
        $dramas->shouldReceive('findByExternalId')->with('DRAMA001')->once()->andReturn($this->drama());
        $episodes->shouldReceive('existsEpisodeNumber')->with(7, 1)->once()->andReturnFalse();
        $episodes->shouldReceive('existsExternalVideoId')->with('DRAMA001_ep01')->once()->andReturnFalse();
        $episode = new DramaEpisode(['external_video_id' => 'DRAMA001_ep01', 'episode_no' => 1]);
        $episode->setAttribute('id', 31);
        $episodes->shouldReceive('create')->once()->with(Mockery::on(function (array $data): bool {
            return $data['title'] === '第 01 集'
                && $data['play_url'] === 'videos/DRAMA001/DRAMA001_ep01.mp4'
                && $data['poster_url'] === 'https://img.example.test/cover.jpg'
                && $data['status'] === 1
                && $data['show_title_arrow'] === 1;
        }))->andReturn($episode);
        $assets->shouldReceive('markUploaded')->with($asset, 31)->once();

        $result = $service->complete(12);

        self::assertSame(31, $result['id']);
        self::assertSame('DRAMA001_ep01', $result['external_video_id']);
    }

    public function testCompleteIsIdempotentAndDoesNotHeadAgain(): void
    {
        self::assertTrue(class_exists(MediaUploadService::class));

        [$service, $dramas, $episodes, $assets, $storage] = $this->service();
        $asset = $this->pendingAsset();
        $asset->setAttribute('status', MediaAsset::STATUS_UPLOADED);
        $asset->setAttribute('episode_id', 31);
        $episode = new DramaEpisode(['external_video_id' => 'DRAMA001_ep01']);
        $episode->setAttribute('id', 31);
        $assets->shouldReceive('transaction')->once()->andReturnUsing(static fn (callable $callback) => $callback());
        $assets->shouldReceive('findByIdForUpdate')->with(12)->once()->andReturn($asset);
        $episodes->shouldReceive('findById')->with(31)->once()->andReturn($episode);
        $storage->shouldNotReceive('head');
        $dramas->shouldNotReceive('findByExternalId');

        self::assertSame(31, $service->complete(12)['id']);
    }

    public function testCompleteMarksMissingObjectAsFailed(): void
    {
        self::assertTrue(class_exists(MediaUploadService::class));

        [$service, $dramas, $episodes, $assets, $storage] = $this->service();
        $asset = $this->pendingAsset();
        $assets->shouldReceive('transaction')->once()->andReturnUsing(static fn (callable $callback) => $callback());
        $assets->shouldReceive('findByIdForUpdate')->andReturn($asset);
        $storage->shouldReceive('head')->andReturnNull();
        $assets->shouldReceive('markFailed')->with($asset, 'R2 对象不存在')->once();

        $this->expectException(BusinessException::class);
        $service->complete(12);
    }

    public function testCompleteRejectsObjectWithDifferentSize(): void
    {
        [$service, $dramas, $episodes, $assets, $storage] = $this->service();
        $asset = $this->pendingAsset();
        $assets->shouldReceive('transaction')->once()->andReturnUsing(static fn (callable $callback) => $callback());
        $assets->shouldReceive('findByIdForUpdate')->andReturn($asset);
        $storage->shouldReceive('head')->andReturn([
            'content_length' => 2048,
            'content_type' => 'video/mp4',
            'etag' => 'etag',
        ]);
        $assets->shouldReceive('markFailed')->with($asset, 'R2 对象大小与预检文件不一致')->once();
        $episodes->shouldNotReceive('create');

        $this->expectException(BusinessException::class);
        $service->complete(12);
    }

    private function service(): array
    {
        $dramas = Mockery::mock(DramaRepositoryInterface::class);
        $episodes = Mockery::mock(EpisodeRepositoryInterface::class);
        $assets = Mockery::mock(MediaAssetRepositoryInterface::class);
        $storage = Mockery::mock(ObjectStorage::class);
        $config = new R2StorageConfig('private-videos', 'public-images', 'https://cdn.example.test', 900, 1800);

        return [new MediaUploadService($dramas, $episodes, $assets, $storage, $config), $dramas, $episodes, $assets, $storage];
    }

    private function drama(): Drama
    {
        $drama = new Drama([
            'external_drama_id' => 'DRAMA001',
            'cover_url' => 'https://img.example.test/cover.jpg',
            'display_author_name' => '运营账号',
        ]);
        $drama->setAttribute('id', 7);
        return $drama;
    }

    private function pendingAsset(): MediaAsset
    {
        $asset = new MediaAsset([
            'bucket' => 'private-videos',
            'object_key' => 'videos/DRAMA001/DRAMA001_ep01.mp4',
            'original_name' => 'DRAMA001_ep01.mp4',
            'size_bytes' => 1024,
            'status' => MediaAsset::STATUS_PENDING,
        ]);
        $asset->setAttribute('id', 12);
        return $asset;
    }

    private function file(): array
    {
        return [
            'name' => 'DRAMA001_ep01.mp4',
            'size' => 1024,
            'mime_type' => 'video/mp4',
            'sha256' => str_repeat('a', 64),
        ];
    }
}
