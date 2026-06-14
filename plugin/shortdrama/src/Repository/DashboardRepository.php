<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Repository;

use Plugin\ShortDrama\Contract\DashboardRepositoryInterface;
use Plugin\ShortDrama\Model\AppUser;
use Plugin\ShortDrama\Model\Drama;
use Plugin\ShortDrama\Model\DramaEpisode;
use Plugin\ShortDrama\Model\DramaEpisodeStat;

final class DashboardRepository implements DashboardRepositoryInterface
{
    public function overview(): array
    {
        return [
            'drama_count' => Drama::query()->count(),
            'online_episode_count' => DramaEpisode::query()->where('status', DramaEpisode::STATUS_ONLINE)->count(),
            'user_count' => AppUser::query()->count(),
            'play_count' => (int) DramaEpisodeStat::query()->sum('play_count'),
        ];
    }

    public function ranking(): array
    {
        return Drama::query()
            ->select(['id', 'external_drama_id', 'title', 'cover_url', 'play_count'])
            ->orderByDesc('play_count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function distribution(): array
    {
        return [
            'status' => Drama::query()->selectRaw('status, COUNT(*) AS count')->groupBy('status')->get()->toArray(),
            'category' => Drama::query()->selectRaw('category, COUNT(*) AS count')->groupBy('category')->get()->toArray(),
        ];
    }
}
