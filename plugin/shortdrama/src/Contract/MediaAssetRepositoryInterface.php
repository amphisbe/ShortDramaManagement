<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Contract;

use Plugin\ShortDrama\Model\MediaAsset;

interface MediaAssetRepositoryInterface
{
    public function transaction(callable $callback): mixed;

    public function reserve(array $attributes): MediaAsset;

    public function findByIdForUpdate(int $id): ?MediaAsset;

    public function existsObjectKey(string $objectKey): bool;

    public function existsSha256(string $sha256): bool;

    public function markUploaded(MediaAsset $asset, int $episodeId): void;

    public function markFailed(MediaAsset $asset, string $reason): void;
}
