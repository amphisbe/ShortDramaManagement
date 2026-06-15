<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Domain;

use DateTimeInterface;
use Plugin\ShortDrama\Model\MediaAsset;

final class MediaAssetReservationPolicy
{
    public function canReset(
        string $status,
        ?DateTimeInterface $expiresAt,
        bool $objectExists,
        DateTimeInterface $now,
    ): bool {
        if ($objectExists || $status === MediaAsset::STATUS_UPLOADED) {
            return false;
        }

        if ($status === MediaAsset::STATUS_FAILED) {
            return true;
        }

        return $status === MediaAsset::STATUS_PENDING
            && $expiresAt !== null
            && $expiresAt <= $now;
    }
}
