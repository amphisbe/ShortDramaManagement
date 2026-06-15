<?php

declare(strict_types=1);

namespace HyperfTests\Feature\ShortDrama;

use App\Exception\BusinessException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Contract\ObjectStorage;
use Plugin\ShortDrama\Controller\MediaController;
use Plugin\ShortDrama\Infrastructure\R2StorageConfig;
use Plugin\ShortDrama\Model\Drama;
use Plugin\ShortDrama\Request\ImagePresignRequest;
use Plugin\ShortDrama\Service\ImageUploadService;
use ReflectionClass;

final class ImageUploadControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public static function mimeTypes(): array
    {
        return [
            ['image/jpeg', 'jpg'],
            ['image/png', 'png'],
            ['image/webp', 'webp'],
        ];
    }

    public function testControllerDeclaresPublicImagePresignRoute(): void
    {
        $method = (new ReflectionClass(MediaController::class))->getMethod('imagePresign');

        self::assertSame(
            '/admin/shortdrama/images/upload/presign',
            $method->getAttributes(\Hyperf\Swagger\Annotation\Post::class)[0]->newInstance()->path,
        );
    }

    public function testRequestLimitsImageTypeAndSize(): void
    {
        self::assertTrue(class_exists(ImagePresignRequest::class));

        $rules = (new ReflectionClass(ImagePresignRequest::class))->newInstanceWithoutConstructor()->rules();

        self::assertSame('required|string|max:24|regex:/^[A-Za-z0-9_-]+$/', $rules['external_drama_id']);
        self::assertSame('required|integer|min:1|max:10485760', $rules['size']);
        self::assertSame('required|string|in:image/jpeg,image/png,image/webp', $rules['mime_type']);
    }

    #[DataProvider('mimeTypes')]
    public function testPresignsSupportedImageAndReturnsStablePublicUrl(string $mimeType, string $extension): void
    {
        self::assertTrue(class_exists(ImageUploadService::class));

        $dramas = Mockery::mock(DramaRepositoryInterface::class);
        $drama = new Drama(['external_drama_id' => 'DRAMA001']);
        $drama->setAttribute('id', 7);
        $dramas->shouldReceive('findByExternalId')->with('DRAMA001')->once()->andReturn($drama);
        $storage = Mockery::mock(ObjectStorage::class);
        $storage->shouldReceive('presignPut')->once()->withArgs(function (
            string $bucket,
            string $key,
            string $contentType,
            int $expires,
        ) use ($mimeType, $extension): bool {
            return $bucket === 'public-images'
                && str_starts_with($key, 'covers/DRAMA001/')
                && str_ends_with($key, '.' . $extension)
                && $contentType === $mimeType
                && $expires === 900;
        })->andReturn('https://upload.example.test/image');
        $service = new ImageUploadService(
            $dramas,
            $storage,
            new R2StorageConfig('private-videos', 'public-images', 'https://cdn.example.test/', 900, 1800),
        );

        $result = $service->presign([
            'external_drama_id' => 'DRAMA001',
            'size' => 1024,
            'mime_type' => $mimeType,
        ]);

        self::assertSame('https://upload.example.test/image', $result['upload_url']);
        self::assertStringStartsWith('https://cdn.example.test/covers/DRAMA001/', $result['public_url']);
        self::assertStringEndsWith('.' . $extension, $result['public_url']);
        self::assertSame(900, $result['expires_in']);
    }

    public function testServiceRejectsUnsupportedOrOversizedImage(): void
    {
        self::assertTrue(class_exists(ImageUploadService::class));

        $dramas = Mockery::mock(DramaRepositoryInterface::class);
        $storage = Mockery::mock(ObjectStorage::class);
        $service = new ImageUploadService($dramas, $storage, new R2StorageConfig());

        foreach ([
            ['external_drama_id' => 'DRAMA001', 'size' => 1024, 'mime_type' => 'image/gif'],
            ['external_drama_id' => 'DRAMA001', 'size' => 10485761, 'mime_type' => 'image/jpeg'],
        ] as $input) {
            try {
                $service->presign($input);
                self::fail('Expected image validation error.');
            } catch (BusinessException) {
                self::assertTrue(true);
            }
        }
        $storage->shouldNotHaveReceived('presignPut');
    }
}
