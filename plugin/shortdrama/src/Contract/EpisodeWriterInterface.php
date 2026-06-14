<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Contract;

use Plugin\ShortDrama\Model\DramaEpisode;

interface EpisodeWriterInterface
{
    public function create(array $data): DramaEpisode;
}
