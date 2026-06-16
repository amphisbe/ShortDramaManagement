<?php

declare(strict_types=1);

namespace HyperfTests\Feature\ShortDrama;

use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Model\MediaAsset;
use Plugin\ShortDrama\Repository\MediaAssetRepository;
use ReflectionMethod;

final class MediaAssetMigrationTest extends TestCase
{
    public function testMigrationCreatesMediaAssetsOnDramaConnection(): void
    {
        $path = BASE_PATH . '/plugin/shortdrama/Database/Migrations/2026_06_14_000001_create_media_assets_table.php';

        self::assertFileExists($path);
        $source = file_get_contents($path);

        self::assertStringContainsString("Db::connection('drama')->getSchemaBuilder()->create('media_assets'", $source);
        self::assertStringNotContainsString('Schema::connection(', $source);
        self::assertStringContainsString("unsignedInteger('episode_id')->nullable()->unique()", $source);
        self::assertStringContainsString("string('object_key', 512)->unique()", $source);
        self::assertStringContainsString("char('sha256', 64)->unique()", $source);
        self::assertStringContainsString("string('status', 24)->index()", $source);
        self::assertStringContainsString("dateTime('reservation_expires_at')->nullable()->index()", $source);
        self::assertStringContainsString("unsignedBigInteger('uploaded_by')", $source);
    }

    public function testMediaAssetModelExposesOnlySupportedStates(): void
    {
        self::assertTrue(class_exists(MediaAsset::class));

        $model = new MediaAsset();

        self::assertSame('drama', $model->getConnectionName());
        self::assertSame('media_assets', $model->getTable());
        self::assertSame(['pending', 'uploaded', 'failed'], MediaAsset::STATUSES);
        self::assertSame('integer', $model->getCasts()['size_bytes'] ?? null);
        self::assertSame('datetime', $model->getCasts()['reservation_expires_at'] ?? null);
    }

    public function testRepositoryProvidesAtomicReservationEntryPoint(): void
    {
        self::assertTrue(class_exists(MediaAssetRepository::class));

        $method = new ReflectionMethod(MediaAssetRepository::class, 'reserve');
        $source = file_get_contents($method->getFileName());

        self::assertTrue($method->isPublic());
        self::assertSame(MediaAsset::class, (string) $method->getReturnType());
        self::assertStringContainsString("Db::connection('drama')->transaction", $source);
        self::assertStringContainsString('lockForUpdate()', $source);
        self::assertStringContainsString('catch (QueryException $exception)', $source);
        self::assertStringContainsString('isUniqueConstraintViolation', $source);
    }
}
