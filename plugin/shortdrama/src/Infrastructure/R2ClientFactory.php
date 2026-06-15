<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Infrastructure;

use Aws\S3\S3Client;
use Psr\Container\ContainerInterface;

final class R2ClientFactory
{
    public function __invoke(ContainerInterface $container): R2ObjectStorage
    {
        $client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => sprintf(
                'https://%s.r2.cloudflarestorage.com',
                (string) env('R2_ACCOUNT_ID', ''),
            ),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => (string) env('R2_ACCESS_KEY_ID', ''),
                'secret' => (string) env('R2_SECRET_ACCESS_KEY', ''),
            ],
        ]);

        return new R2ObjectStorage($client);
    }
}
