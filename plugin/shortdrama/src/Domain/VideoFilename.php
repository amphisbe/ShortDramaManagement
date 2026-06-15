<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Domain;

final class VideoFilename
{
    private const PATTERN = '/^(?<drama>[A-Za-z0-9_-]+)_ep(?<episode>\d{2,})\.mp4$/i';

    private function __construct(
        public readonly string $originalName,
        public readonly string $externalDramaId,
        public readonly string $externalVideoId,
        public readonly int $episodeNo,
        public readonly string $objectKey,
    ) {
    }

    public static function tryParse(string $name): ?self
    {
        if (preg_match(self::PATTERN, $name, $matches) !== 1) {
            return null;
        }

        $externalDramaId = $matches['drama'];
        $externalVideoId = pathinfo($name, PATHINFO_FILENAME);

        return new self(
            $name,
            $externalDramaId,
            $externalVideoId,
            (int) $matches['episode'],
            sprintf('videos/%s/%s.mp4', $externalDramaId, $externalVideoId),
        );
    }
}
