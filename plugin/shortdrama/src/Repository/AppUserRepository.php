<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Repository;

use Hyperf\Collection\Arr;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use Hyperf\Paginator\AbstractPaginator;
use Plugin\ShortDrama\Contract\AppUserRepositoryInterface;
use Plugin\ShortDrama\Model\AppUser;

final class AppUserRepository implements AppUserRepositoryInterface
{
    public function __construct(private readonly AppUser $model) {}

    public function query(array $params = []): Builder
    {
        $keyword = Arr::get($params, 'keyword');
        $keyword = is_string($keyword) ? trim($keyword) : null;
        $createdAt = Arr::get($params, 'created_at');
        $createdAt = $this->validDateRange($createdAt) ? $createdAt : null;

        return $this->model->newQuery()
            ->select('users.*')
            ->selectRaw('(SELECT COUNT(*) FROM user_episode_likes WHERE user_episode_likes.user_id = users.id) AS like_count')
            ->selectRaw('(SELECT COUNT(*) FROM user_drama_favorites WHERE user_drama_favorites.user_id = users.id) AS favorite_count')
            ->selectRaw('(SELECT COUNT(*) FROM user_episode_progress WHERE user_episode_progress.user_id = users.id) AS progress_count')
            ->when(Arr::exists($params, 'status'), static function (Builder $query) use ($params): void {
                $query->where('status', Arr::get($params, 'status'));
            })
            ->when($keyword !== null && $keyword !== '', static function (Builder $query) use ($keyword): void {
                $query->where(static function (Builder $query) use ($keyword): void {
                    $query->where('nickname', 'like', '%' . $keyword . '%')
                        ->orWhere('external_user_id', 'like', '%' . $keyword . '%');
                });
            })
            ->when($createdAt !== null, static function (Builder $query) use ($createdAt): void {
                $query->whereBetween('created_at', [
                    trim($createdAt[0]) . ' 00:00:00',
                    trim($createdAt[1]) . ' 23:59:59',
                ]);
            });
    }

    private function validDateRange(mixed $range): bool
    {
        return is_array($range)
            && count($range) >= 2
            && isset($range[0], $range[1])
            && is_string($range[0])
            && is_string($range[1])
            && trim($range[0]) !== ''
            && trim($range[1]) !== '';
    }

    public function page(array $params, ?int $page = null, ?int $pageSize = null): array
    {
        $result = $this->query($params)->paginate(perPage: $pageSize, page: $page);
        $items = $result instanceof AbstractPaginator
            ? $result->getCollection()
            : Collection::make($result->items());

        return ['list' => $this->handleItems($items)->toArray(), 'total' => $result->total()];
    }

    public function handleItems(Collection $items): Collection
    {
        return $items;
    }

    public function findById(mixed $id): ?AppUser
    {
        return $this->query()->whereKey($id)->first();
    }

    public function updateStatus(int $id, int $status): bool
    {
        return (bool) $this->model->newQuery()->whereKey($id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
