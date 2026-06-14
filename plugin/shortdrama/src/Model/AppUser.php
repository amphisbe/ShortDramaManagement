<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Model;

use Hyperf\DbConnection\Model\Model;

final class AppUser extends Model
{
    public const STATUS_DISABLED = 0;

    public const STATUS_NORMAL = 1;

    public bool $timestamps = true;

    protected ?string $connection = 'drama';

    protected ?string $table = 'users';

    protected array $fillable = [
        'id', 'external_user_id', 'nickname', 'avatar_url', 'status', 'created_at', 'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
