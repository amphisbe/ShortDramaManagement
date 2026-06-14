<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Contract;

use Plugin\ShortDrama\Model\DramaEpisode;

interface EpisodeRepositoryInterface
{
    public function page(array $params, ?int $page = null, ?int $pageSize = null): array;

    public function findById(mixed $id): ?DramaEpisode;

    public function create(array $data): DramaEpisode;

    public function updateById(mixed $id, array $data): bool;

    public function existsEpisodeNumber(int $dramaId, int $episodeNo, ?int $idExcept = null): bool;

    public function existsExternalVideoId(string $externalVideoId, ?int $idExcept = null): bool;

    public function countByIds(array $ids): int;

    public function updateStatusByIds(array $ids, int $status): int;

    public function batchUpdateStatus(array $ids, int $status): void;
}
