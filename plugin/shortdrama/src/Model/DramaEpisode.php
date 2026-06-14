<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Model;

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\DbConnection\Model\Model;

final class DramaEpisode extends Model
{
    public const STATUS_OFFLINE = 0;

    public const STATUS_ONLINE = 1;

    public bool $timestamps = true;

    protected ?string $connection = 'drama';

    protected ?string $table = 'drama_episodes';

    protected array $fillable = [
        'drama_id', 'external_video_id', 'episode_no', 'title', 'play_url',
        'poster_url', 'duration_seconds', 'sort_order', 'status', 'display_nickname',
        'loop', 'play_ing', 'muted', 'is_playing', 'show_title_arrow',
        'show_look_all_btn', 'look_all_btn_text', 'show_bottom_area',
        'bottom_area_btn_text', 'tool_info_json',
    ];

    protected array $casts = [
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
    ];

    public function drama(): BelongsTo
    {
        return $this->belongsTo(Drama::class, 'drama_id');
    }

    public function stats(): HasOne
    {
        return $this->hasOne(DramaEpisodeStat::class, 'episode_id');
    }
}
