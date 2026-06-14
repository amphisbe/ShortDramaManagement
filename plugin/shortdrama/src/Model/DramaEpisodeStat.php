<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Model;

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

final class DramaEpisodeStat extends Model
{
    public bool $timestamps = true;

    public bool $incrementing = false;

    protected ?string $connection = 'drama';

    protected ?string $table = 'drama_episode_stats';

    protected string $primaryKey = 'episode_id';

    protected array $fillable = [
        'episode_id', 'like_count', 'comment_count', 'share_count', 'play_count',
        'favorite_count', 'created_at', 'updated_at',
    ];

    protected array $casts = [
        'episode_id' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'share_count' => 'integer',
        'play_count' => 'integer',
        'favorite_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(DramaEpisode::class, 'episode_id');
    }
}
