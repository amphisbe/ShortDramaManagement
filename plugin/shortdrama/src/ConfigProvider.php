<?php

declare(strict_types=1);

namespace Plugin\ShortDrama;

use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Contract\DramaWriterInterface;
use Plugin\ShortDrama\Contract\AppUserRepositoryInterface;
use Plugin\ShortDrama\Contract\DashboardRepositoryInterface;
use Plugin\ShortDrama\Contract\EpisodeRepositoryInterface;
use Plugin\ShortDrama\Contract\EpisodeWriterInterface;
use Plugin\ShortDrama\Contract\MediaAssetRepositoryInterface;
use Plugin\ShortDrama\Contract\ObjectStorage;
use Plugin\ShortDrama\Infrastructure\R2ClientFactory;
use Plugin\ShortDrama\Repository\AppUserRepository;
use Plugin\ShortDrama\Repository\DashboardRepository;
use Plugin\ShortDrama\Repository\DramaRepository;
use Plugin\ShortDrama\Repository\EpisodeRepository;
use Plugin\ShortDrama\Repository\MediaAssetRepository;
use Plugin\ShortDrama\Service\DramaService;
use Plugin\ShortDrama\Service\EpisodeService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                DramaRepositoryInterface::class => DramaRepository::class,
                EpisodeRepositoryInterface::class => EpisodeRepository::class,
                AppUserRepositoryInterface::class => AppUserRepository::class,
                DashboardRepositoryInterface::class => DashboardRepository::class,
                DramaWriterInterface::class => DramaService::class,
                EpisodeWriterInterface::class => EpisodeService::class,
                MediaAssetRepositoryInterface::class => MediaAssetRepository::class,
                ObjectStorage::class => R2ClientFactory::class,
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
