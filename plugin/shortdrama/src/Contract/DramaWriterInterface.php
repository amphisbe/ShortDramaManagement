<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Contract;

use Plugin\ShortDrama\Model\Drama;

interface DramaWriterInterface
{
    public function create(array $data): Drama;
}
