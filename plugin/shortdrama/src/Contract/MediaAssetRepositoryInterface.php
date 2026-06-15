<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Contract;

use Plugin\ShortDrama\Model\MediaAsset;

interface MediaAssetRepositoryInterface
{
    public function reserve(array $attributes): MediaAsset;

    public function findByIdForUpdate(int $id): ?MediaAsset;

    public function existsObjectKey(string $objectKey): bool;

    public function existsSha256(string $sha256): bool;
}
