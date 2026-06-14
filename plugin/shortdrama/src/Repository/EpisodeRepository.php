<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Repository;

use Hyperf\Collection\Arr;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use Hyperf\Paginator\AbstractPaginator;
use Plugin\ShortDrama\Model\DramaEpisode;

final class EpisodeRepository
{
    public function __construct(private readonly DramaEpisode $model) {}

    public function query(array $params = []): Builder
    {
        return $this->model->newQuery()
            ->when(Arr::exists($params, 'drama_id'), static function (Builder $query) use ($params): void {
                $query->where('drama_id', Arr::get($params, 'drama_id'));
            })
            ->when(Arr::exists($params, 'status'), static function (Builder $query) use ($params): void {
                $query->where('status', Arr::get($params, 'status'));
            })
            ->when(Arr::get($params, 'keyword'), static function (Builder $query, string $keyword): void {
                $query->where(static function (Builder $query) use ($keyword): void {
                    $query->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('external_video_id', 'like', '%' . $keyword . '%');
                });
            })
            ->when(Arr::get($params, 'created_at'), static function (Builder $query, array $range): void {
                $query->whereBetween('created_at', [
                    $range[0] . ' 00:00:00',
                    $range[1] . ' 23:59:59',
                ]);
            });
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

    public function findById(mixed $id): ?DramaEpisode
    {
        return $this->query()->whereKey($id)->first();
    }

    public function create(array $data): DramaEpisode
    {
        return $this->model->newQuery()->create($data);
    }

    public function updateById(mixed $id, array $data): bool
    {
        return (bool) $this->model->newQuery()->whereKey($id)->first()?->update($data);
    }
}
