<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Model;

use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

final class MediaAsset extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_UPLOADED, self::STATUS_FAILED];

    public bool $timestamps = true;

    protected ?string $connection = 'drama';

    protected ?string $table = 'media_assets';

    protected array $fillable = [
        'episode_id', 'bucket', 'object_key', 'sha256', 'original_name', 'size_bytes',
        'mime_type', 'status', 'failure_reason', 'reservation_expires_at', 'uploaded_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'episode_id' => 'integer',
        'size_bytes' => 'integer',
        'uploaded_by' => 'integer',
        'reservation_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(DramaEpisode::class, 'episode_id');
    }
}
