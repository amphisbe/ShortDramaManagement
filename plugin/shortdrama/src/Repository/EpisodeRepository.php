<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Repository;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use Hyperf\Collection\Arr;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;
use Hyperf\Paginator\AbstractPaginator;
use Plugin\ShortDrama\Contract\EpisodeRepositoryInterface;
use Plugin\ShortDrama\Model\DramaEpisode;

final class EpisodeRepository implements EpisodeRepositoryInterface
{
    public function __construct(private readonly DramaEpisode $model) {}

    public function query(array $params = []): Builder
    {
        $keyword = Arr::get($params, 'keyword');
        $keyword = is_string($keyword) ? trim($keyword) : null;
        $createdAt = Arr::get($params, 'created_at');
        $createdAt = $this->validDateRange($createdAt) ? $createdAt : null;

        return $this->model->newQuery()
            ->when(Arr::exists($params, 'drama_id'), static function (Builder $query) use ($params): void {
                $query->where('drama_id', Arr::get($params, 'drama_id'));
            })
            ->when(Arr::exists($params, 'status'), static function (Builder $query) use ($params): void {
                $query->where('status', Arr::get($params, 'status'));
            })
            ->when($keyword !== null && $keyword !== '', static function (Builder $query) use ($keyword): void {
                $query->where(static function (Builder $query) use ($keyword): void {
                    $query->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('external_video_id', 'like', '%' . $keyword . '%');
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

    public function existsEpisodeNumber(int $dramaId, int $episodeNo, ?int $idExcept = null): bool
    {
        return $this->model->newQuery()
            ->where('drama_id', $dramaId)
            ->where('episode_no', $episodeNo)
            ->when($idExcept !== null, static fn (Builder $query) => $query->where('id', '<>', $idExcept))
            ->exists();
    }

    public function existsExternalVideoId(string $externalVideoId, ?int $idExcept = null): bool
    {
        return $this->model->newQuery()
            ->where('external_video_id', $externalVideoId)
            ->when($idExcept !== null, static fn (Builder $query) => $query->where('id', '<>', $idExcept))
            ->exists();
    }

    public function countByIds(array $ids): int
    {
        return $this->model->newQuery()->whereIn('id', $ids)->count();
    }

    public function updateStatusByIds(array $ids, int $status): int
    {
        return $this->model->newQuery()->whereIn('id', $ids)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function batchUpdateStatus(array $ids, int $status): void
    {
        $ids = array_values(array_unique($ids));
        Db::connection('drama')->transaction(function () use ($ids, $status): void {
            if ($this->countByIds($ids) !== count($ids)) {
                throw new BusinessException(ResultCode::NOT_FOUND);
            }
            $this->updateStatusByIds($ids, $status);
        });
    }
}
