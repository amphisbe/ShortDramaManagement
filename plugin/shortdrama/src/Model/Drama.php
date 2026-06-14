<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Model;

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Model\Model;

final class Drama extends Model
{
    public const STATUS_OFFLINE = 0;

    public const STATUS_SERIALIZING = 1;

    public const STATUS_COMPLETED = 2;

    public const APP_VISIBLE_STATUSES = [self::STATUS_SERIALIZING, self::STATUS_COMPLETED];

    public bool $timestamps = true;

    protected ?string $connection = 'drama';

    protected ?string $table = 'dramas';

    protected array $fillable = [
        'external_drama_id', 'title', 'display_author_name', 'author_user_id',
        'total_episodes', 'cover_url', 'vip_free', 'status', 'description', 'category',
        'tags', 'play_count', 'follow_count',
    ];

    protected array $casts = [
        'id' => 'integer',
        'author_user_id' => 'integer',
        'total_episodes' => 'integer',
        'vip_free' => 'integer',
        'status' => 'integer',
        'play_count' => 'integer',
        'follow_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function episodes(): HasMany
    {
        return $this->hasMany(DramaEpisode::class, 'drama_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'author_user_id');
    }
}
