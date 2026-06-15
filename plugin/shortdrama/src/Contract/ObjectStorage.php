<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Contract;

interface ObjectStorage
{
    public function presignPut(string $bucket, string $key, string $contentType, int $expiresIn): string;

    public function presignGet(string $bucket, string $key, int $expiresIn): string;

    public function head(string $bucket, string $key): ?array;

    public function delete(string $bucket, string $key): void;
}
