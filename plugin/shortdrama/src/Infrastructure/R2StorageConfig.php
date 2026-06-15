<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Infrastructure;

final class R2StorageConfig
{
    public readonly string $privateBucket;

    public readonly string $publicBucket;

    public readonly string $publicBaseUrl;

    public readonly int $putExpires;

    public readonly int $getExpires;

    public function __construct(
        ?string $privateBucket = null,
        ?string $publicBucket = null,
        ?string $publicBaseUrl = null,
        ?int $putExpires = null,
        ?int $getExpires = null,
    ) {
        $this->privateBucket = $privateBucket ?? (string) env('R2_PRIVATE_BUCKET', '');
        $this->publicBucket = $publicBucket ?? (string) env('R2_PUBLIC_BUCKET', '');
        $this->publicBaseUrl = rtrim($publicBaseUrl ?? (string) env('R2_PUBLIC_BASE_URL', ''), '/');
        $this->putExpires = $putExpires ?? (int) env('R2_PUT_EXPIRES', 900);
        $this->getExpires = $getExpires ?? (int) env('R2_GET_EXPIRES', 1800);
    }
}
