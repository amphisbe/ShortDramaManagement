<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\ConfigProvider;
use Plugin\ShortDrama\Contract\ObjectStorage;
use Plugin\ShortDrama\Infrastructure\R2ClientFactory;
use Plugin\ShortDrama\Infrastructure\R2ObjectStorage;

final class R2ObjectStorageTest extends TestCase
{
    public function testStorageContractIsBoundToTheR2Factory(): void
    {
        self::assertTrue(interface_exists(ObjectStorage::class));

        $dependencies = (new ConfigProvider())()['dependencies'];

        self::assertSame(R2ClientFactory::class, $dependencies[ObjectStorage::class] ?? null);
    }

    public function testPresignsPutAndGetRequests(): void
    {
        self::assertTrue(class_exists(R2ObjectStorage::class));

        $storage = new R2ObjectStorage($this->client(new MockHandler()));

        $putUrl = $storage->presignPut('private-videos', 'videos/DRAMA001/DRAMA001_ep01.mp4', 'video/mp4', 900);
        $getUrl = $storage->presignGet('private-videos', 'videos/DRAMA001/DRAMA001_ep01.mp4', 1800);

        self::assertStringContainsString('/private-videos/videos/DRAMA001/DRAMA001_ep01.mp4', $putUrl);
        self::assertStringContainsString('X-Amz-Signature=', $putUrl);
        self::assertStringContainsString('/private-videos/videos/DRAMA001/DRAMA001_ep01.mp4', $getUrl);
        self::assertStringContainsString('X-Amz-Signature=', $getUrl);
    }

    public function testReadsObjectMetadataAndDeletesObject(): void
    {
        self::assertTrue(class_exists(R2ObjectStorage::class));

        $handler = new MockHandler();
        $handler->append(new Result([
            'ContentLength' => 1024,
            'ContentType' => 'video/mp4',
            'ETag' => 'etag-value',
        ]));
        $handler->append(new Result());
        $storage = new R2ObjectStorage($this->client($handler));

        self::assertSame([
            'content_length' => 1024,
            'content_type' => 'video/mp4',
            'etag' => 'etag-value',
        ], $storage->head('private-videos', 'videos/DRAMA001/DRAMA001_ep01.mp4'));

        $storage->delete('private-videos', 'videos/DRAMA001/DRAMA001_ep01.mp4');
        self::assertTrue(true);
    }

    public function testReturnsNullWhenObjectDoesNotExist(): void
    {
        self::assertTrue(class_exists(R2ObjectStorage::class));

        $handler = new MockHandler();
        $handler->append(static function (CommandInterface $command): AwsException {
            return new AwsException(
                'Object not found',
                $command,
                [
                    'code' => 'NoSuchKey',
                    'request' => new Request('HEAD', 'https://example.test/object'),
                    'response' => new Response(404),
                ],
            );
        });
        $storage = new R2ObjectStorage($this->client($handler));

        self::assertNull($storage->head('private-videos', 'missing.mp4'));
    }

    private function client(MockHandler $handler): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => 'https://account-id.r2.cloudflarestorage.com',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => 'access-key',
                'secret' => 'secret-key',
            ],
            'handler' => $handler,
        ]);
    }
}
