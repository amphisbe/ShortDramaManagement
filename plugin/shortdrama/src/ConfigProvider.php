<?php

declare(strict_types=1);

namespace Plugin\ShortDrama;

use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Contract\EpisodeRepositoryInterface;
use Plugin\ShortDrama\Repository\DramaRepository;
use Plugin\ShortDrama\Repository\EpisodeRepository;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                DramaRepositoryInterface::class => DramaRepository::class,
                EpisodeRepositoryInterface::class => EpisodeRepository::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
