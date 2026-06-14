<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\Database\Model\SoftDeletes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Model\AppUser;
use Plugin\ShortDrama\Model\Drama;
use Plugin\ShortDrama\Model\DramaEpisode;
use Plugin\ShortDrama\Model\DramaEpisodeStat;

final class ModelContractTest extends TestCase
{
    public static function modelProvider(): array
    {
        return [
            'drama' => [
                new Drama(),
                'dramas',
                [
                    'external_drama_id', 'title', 'display_author_name', 'author_user_id',
                    'total_episodes', 'cover_url', 'vip_free', 'status', 'description', 'category',
                    'tags', 'play_count', 'follow_count',
                ],
                [
                    'id' => 'integer',
                    'author_user_id' => 'integer',
                    'total_episodes' => 'integer',
                    'vip_free' => 'integer',
                    'status' => 'integer',
                    'play_count' => 'integer',
                    'follow_count' => 'integer',
                    'created_at' => 'datetime',
                    'updated_at' => 'datetime',
                ],
            ],
            'episode' => [
                new DramaEpisode(),
                'drama_episodes',
                [
                    'drama_id', 'external_video_id', 'episode_no', 'title', 'play_url',
                    'poster_url', 'duration_seconds', 'sort_order', 'status', 'display_nickname',
                    'loop', 'play_ing', 'muted', 'is_playing', 'show_title_arrow',
                    'show_look_all_btn', 'look_all_btn_text', 'show_bottom_area',
                    'bottom_area_btn_text', 'tool_info_json',
                ],
                [
                    'id' => 'integer',
                    'drama_id' => 'integer',
                    'episode_no' => 'integer',
                    'duration_seconds' => 'integer',
                    'sort_order' => 'integer',
                    'status' => 'integer',
                    'loop' => 'integer',
                    'play_ing' => 'integer',
                    'muted' => 'integer',
                    'is_playing' => 'integer',
                    'show_title_arrow' => 'integer',
                    'show_look_all_btn' => 'integer',
                    'show_bottom_area' => 'integer',
                    'tool_info_json' => 'json',
                    'created_at' => 'datetime',
                    'updated_at' => 'datetime',
                ],
            ],
            'app user' => [
                new AppUser(),
                'users',
                ['external_user_id', 'nickname', 'avatar_url', 'status'],
                [
                    'id' => 'integer',
                    'status' => 'integer',
                    'created_at' => 'datetime',
                    'updated_at' => 'datetime',
                ],
            ],
            'episode stat' => [
                new DramaEpisodeStat(),
                'drama_episode_stats',
                [
                    'episode_id', 'like_count', 'comment_count', 'share_count', 'play_count',
                    'favorite_count',
                ],
                [
                    'episode_id' => 'integer',
                    'like_count' => 'integer',
                    'comment_count' => 'integer',
                    'share_count' => 'integer',
                    'play_count' => 'integer',
                    'favorite_count' => 'integer',
                    'created_at' => 'datetime',
                    'updated_at' => 'datetime',
                ],
            ],
        ];
    }

    #[DataProvider('modelProvider')]
    public function testModelsMatchExistingDramaTables(
        object $model,
        string $table,
        array $fillable,
        array $casts
    ): void {
        self::assertSame('drama', $model->getConnectionName());
        self::assertSame($table, $model->getTable());
        self::assertTrue($model->timestamps);
        self::assertSame($fillable, $model->getFillable());

        foreach ($casts as $field => $cast) {
            self::assertSame($cast, $model->getCasts()[$field] ?? null, $field);
        }

        self::assertNotContains(SoftDeletes::class, class_uses_recursive($model));
    }

    public function testBusinessStatusConstantsAreStable(): void
    {
        self::assertSame(0, Drama::STATUS_OFFLINE);
        self::assertSame(1, Drama::STATUS_SERIALIZING);
        self::assertSame(2, Drama::STATUS_COMPLETED);
        self::assertSame([1, 2], Drama::APP_VISIBLE_STATUSES);
        self::assertSame(0, DramaEpisode::STATUS_OFFLINE);
        self::assertSame(1, DramaEpisode::STATUS_ONLINE);
        self::assertSame(0, AppUser::STATUS_DISABLED);
        self::assertSame(1, AppUser::STATUS_NORMAL);
    }

    public function testDramaRelationshipsUseExpectedRelationTypes(): void
    {
        $author = (new Drama())->author();

        self::assertInstanceOf(HasMany::class, (new Drama())->episodes());
        self::assertInstanceOf(BelongsTo::class, $author);
        self::assertSame('author_user_id', $author->getForeignKeyName());
        self::assertInstanceOf(AppUser::class, $author->getRelated());
        self::assertInstanceOf(BelongsTo::class, (new DramaEpisode())->drama());
        self::assertInstanceOf(HasOne::class, (new DramaEpisode())->stats());
        self::assertInstanceOf(BelongsTo::class, (new DramaEpisodeStat())->episode());
    }

    public function testMassAssignmentCannotOverrideManagedColumns(): void
    {
        foreach ([new Drama(), new DramaEpisode(), new AppUser()] as $model) {
            $model->fill([
                'id' => 99,
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-02 00:00:00',
            ]);

            self::assertNull($model->getAttribute('id'));
            self::assertNull($model->getAttribute('created_at'));
            self::assertNull($model->getAttribute('updated_at'));
        }

        $stat = new DramaEpisodeStat();
        $stat->fill([
            'episode_id' => 7,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-02 00:00:00',
        ]);

        self::assertSame(7, $stat->getAttribute('episode_id'));
        self::assertNull($stat->getAttribute('created_at'));
        self::assertNull($stat->getAttribute('updated_at'));
    }

    public function testEpisodeStatUsesEpisodeIdAsNonIncrementingPrimaryKey(): void
    {
        $model = new DramaEpisodeStat();

        self::assertSame('episode_id', $model->getKeyName());
        self::assertFalse($model->getIncrementing());
    }
}
