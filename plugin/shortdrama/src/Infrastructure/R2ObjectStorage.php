<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Infrastructure;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Plugin\ShortDrama\Contract\ObjectStorage;

final class R2ObjectStorage implements ObjectStorage
{
    public function __construct(private readonly S3Client $client)
    {
    }

    public function presignPut(string $bucket, string $key, string $contentType, int $expiresIn): string
    {
        $command = $this->client->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key' => $key,
            'ContentType' => $contentType,
        ]);

        return (string) $this->client
            ->createPresignedRequest($command, sprintf('+%d seconds', $expiresIn))
            ->getUri();
    }

    public function presignGet(string $bucket, string $key, int $expiresIn): string
    {
        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $key,
        ]);

        return (string) $this->client
            ->createPresignedRequest($command, sprintf('+%d seconds', $expiresIn))
            ->getUri();
    }

    public function head(string $bucket, string $key): ?array
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);
        } catch (AwsException $exception) {
            if ($exception->getStatusCode() === 404 || $exception->getAwsErrorCode() === 'NoSuchKey') {
                return null;
            }

            throw $exception;
        }

        return [
            'content_length' => (int) $result->get('ContentLength'),
            'content_type' => (string) $result->get('ContentType'),
            'etag' => (string) $result->get('ETag'),
        ];
    }

    public function delete(string $bucket, string $key): void
    {
        $this->client->deleteObject([
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
    }
}
