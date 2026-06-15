<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Exception;

use Plugin\ShortDrama\Model\MediaAsset;
use RuntimeException;

final class MediaAssetReservationConflict extends RuntimeException
{
    public function __construct(public readonly MediaAsset $asset)
    {
        parent::__construct('MEDIA_ASSET_CONFLICT');
    }
}
