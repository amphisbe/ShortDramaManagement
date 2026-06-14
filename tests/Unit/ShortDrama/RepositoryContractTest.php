<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use Hyperf\Collection\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Model\AppUser;
use Plugin\ShortDrama\Model\Drama;
use Plugin\ShortDrama\Model\DramaEpisode;
use Plugin\ShortDrama\Repository\AppUserRepository;
use Plugin\ShortDrama\Repository\DramaRepository;
use Plugin\ShortDrama\Repository\EpisodeRepository;
use ReflectionClass;

final class RepositoryContractTest extends TestCase
{
    public static function repositoryProvider(): array
    {
        return [
            'drama' => [DramaRepository::class, new Drama()],
            'episode' => [EpisodeRepository::class, new DramaEpisode()],
            'app user' => [AppUserRepository::class, new AppUser()],
        ];
    }

    #[DataProvider('repositoryProvider')]
    public function testRepositoriesExposeNoBusinessDeletionApi(string $repositoryClass, object $model): void
    {
        $reflection = new ReflectionClass($repositoryClass);
        $repository = $reflection->newInstance($model);
        $publicMethods = get_class_methods($repository);

        self::assertNull($reflection->getParentClass() ?: null);
        self::assertNotContains('delete', $publicMethods);
        self::assertNotContains('deleteById', $publicMethods);
        self::assertNotContains('forceDelete', $publicMethods);
        self::assertNotContains('forceDeleteById', $publicMethods);
    }

    public function testDramaQueryIncludesEpisodeCountAndAllFiltersIncludingOffline(): void
    {
        $query = (new DramaRepository(new Drama()))->query([
            'keyword' => 'winter',
            'status' => 0,
            'category' => 'urban',
            'created_at' => ['2026-01-02', '2026-01-09'],
        ]);

        $sql = strtolower($query->toSql());

        self::assertStringContainsString('uploaded_episodes', $sql);
        self::assertStringContainsString('title', $sql);
        self::assertStringContainsString('external_drama_id', $sql);
        self::assertStringContainsString('status', $sql);
        self::assertStringContainsString('category', $sql);
        self::assertStringContainsString('created_at', $sql);
        self::assertSame(
            ['%winter%', '%winter%', 0, 'urban', '2026-01-02 00:00:00', '2026-01-09 23:59:59'],
            $query->getBindings()
        );
    }

    public function testDramaItemsAppendEpisodeProgress(): void
    {
        $items = Collection::make([
            ['id' => 1, 'uploaded_episodes' => 72, 'total_episodes' => 80],
        ]);

        $formatted = (new DramaRepository(new Drama()))->handleItems($items);

        self::assertSame('72/80', $formatted->first()['episode_progress']);
    }

    public function testEpisodeQuerySupportsItsFiltersIncludingOffline(): void
    {
        $query = (new EpisodeRepository(new DramaEpisode()))->query([
            'drama_id' => 12,
            'status' => 0,
            'keyword' => 'opening',
            'created_at' => ['2026-02-01', '2026-02-28'],
        ]);

        $sql = strtolower($query->toSql());

        self::assertStringContainsString('drama_id', $sql);
        self::assertStringContainsString('status', $sql);
        self::assertStringContainsString('title', $sql);
        self::assertStringContainsString('external_video_id', $sql);
        self::assertSame(
            [12, 0, '%opening%', '%opening%', '2026-02-01 00:00:00', '2026-02-28 23:59:59'],
            $query->getBindings()
        );
    }

    public function testAppUserQuerySupportsItsFiltersIncludingDisabled(): void
    {
        $query = (new AppUserRepository(new AppUser()))->query([
            'status' => 0,
            'keyword' => 'alice',
            'created_at' => ['2026-03-01', '2026-03-31'],
        ]);

        $sql = strtolower($query->toSql());

        self::assertStringContainsString('status', $sql);
        self::assertStringContainsString('nickname', $sql);
        self::assertStringContainsString('external_user_id', $sql);
        self::assertSame(
            [0, '%alice%', '%alice%', '2026-03-01 00:00:00', '2026-03-31 23:59:59'],
            $query->getBindings()
        );
    }
}
