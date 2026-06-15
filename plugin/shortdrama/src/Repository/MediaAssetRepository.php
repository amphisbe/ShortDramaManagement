<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Repository;

use DateTimeImmutable;
use DateTimeInterface;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;
use Plugin\ShortDrama\Contract\ObjectStorage;
use Plugin\ShortDrama\Contract\MediaAssetRepositoryInterface;
use Plugin\ShortDrama\Domain\MediaAssetReservationPolicy;
use Plugin\ShortDrama\Exception\MediaAssetReservationConflict;
use Plugin\ShortDrama\Model\MediaAsset;

final class MediaAssetRepository implements MediaAssetRepositoryInterface
{
    public function __construct(
        private readonly MediaAsset $model,
        private readonly ObjectStorage $storage,
        private readonly MediaAssetReservationPolicy $policy,
    ) {
    }

    public function reserve(array $attributes): MediaAsset
    {
        try {
            return $this->reserveInTransaction($attributes);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            return $this->reserveInTransaction($attributes);
        }
    }

    private function reserveInTransaction(array $attributes): MediaAsset
    {
        return Db::connection('drama')->transaction(function () use ($attributes): MediaAsset {
            $existing = $this->conflictQuery($attributes)->lockForUpdate()->first();
            if (! $existing instanceof MediaAsset) {
                return $this->model->newQuery()->create($this->pendingAttributes($attributes));
            }

            $objectExists = $this->storage->head($existing->bucket, $existing->object_key) !== null;
            $expiresAt = $existing->reservation_expires_at;
            if (is_string($expiresAt)) {
                $expiresAt = new DateTimeImmutable($expiresAt);
            }

            if (! $this->policy->canReset(
                $existing->status,
                $expiresAt instanceof DateTimeInterface ? $expiresAt : null,
                $objectExists,
                new DateTimeImmutable(),
            )) {
                throw new MediaAssetReservationConflict($existing);
            }

            $existing->fill($this->pendingAttributes($attributes));
            $existing->save();

            return $existing;
        });
    }

    public function findByIdForUpdate(int $id): ?MediaAsset
    {
        return $this->model->newQuery()->whereKey($id)->lockForUpdate()->first();
    }

    public function existsObjectKey(string $objectKey): bool
    {
        return $this->model->newQuery()->where('object_key', $objectKey)->exists();
    }

    public function existsSha256(string $sha256): bool
    {
        return $this->model->newQuery()->where('sha256', $sha256)->exists();
    }

    private function conflictQuery(array $attributes): Builder
    {
        return $this->model->newQuery()->where(static function (Builder $query) use ($attributes): void {
            $query->where('object_key', $attributes['object_key'])
                ->orWhere('sha256', $attributes['sha256']);
        });
    }

    private function pendingAttributes(array $attributes): array
    {
        return [
            ...$attributes,
            'episode_id' => null,
            'status' => MediaAsset::STATUS_PENDING,
            'failure_reason' => null,
        ];
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            || str_contains(strtolower($exception->getMessage()), 'duplicate');
    }
}
