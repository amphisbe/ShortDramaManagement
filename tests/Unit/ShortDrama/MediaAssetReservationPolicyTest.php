<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Domain\MediaAssetReservationPolicy;

final class MediaAssetReservationPolicyTest extends TestCase
{
    public static function resetCases(): array
    {
        return [
            'pending reservation still active' => ['pending', '2026-06-15 10:01:00', false, false],
            'expired pending but object exists' => ['pending', '2026-06-15 09:59:00', true, false],
            'expired pending without object' => ['pending', '2026-06-15 09:59:00', false, true],
            'uploaded asset is immutable' => ['uploaded', '2026-06-15 09:59:00', false, false],
            'failed asset without object' => ['failed', null, false, true],
            'failed asset with object' => ['failed', null, true, false],
        ];
    }

    #[DataProvider('resetCases')]
    public function testOnlyAbandonedReservationsCanBeReset(
        string $status,
        ?string $expiresAt,
        bool $objectExists,
        bool $expected,
    ): void {
        self::assertTrue(class_exists(MediaAssetReservationPolicy::class));

        $policy = new MediaAssetReservationPolicy();

        self::assertSame($expected, $policy->canReset(
            $status,
            $expiresAt === null ? null : new DateTimeImmutable($expiresAt),
            $objectExists,
            new DateTimeImmutable('2026-06-15 10:00:00'),
        ));
    }
}
