<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Service;

use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Contract\EpisodeRepositoryInterface;
use Plugin\ShortDrama\Contract\MediaAssetRepositoryInterface;
use Plugin\ShortDrama\Domain\VideoFilename;

final class MediaValidationService
{
    private const MESSAGES = [
        'INVALID_FILENAME' => '文件名格式应为 external_drama_id_ep01.mp4',
        'DRAMA_NOT_FOUND' => '文件名中的短剧 ID 不存在',
        'EPISODE_EXISTS' => '该短剧集数已存在',
        'VIDEO_ID_EXISTS' => '视频 ID 已存在',
        'HASH_EXISTS' => '相同内容的视频已上传',
        'DUPLICATE_IN_BATCH' => '本批次存在重复文件',
    ];

    public function __construct(
        private readonly DramaRepositoryInterface $dramas,
        private readonly EpisodeRepositoryInterface $episodes,
        private readonly MediaAssetRepositoryInterface $assets,
    ) {
    }

    public function check(array $files): array
    {
        $results = [];
        $seenHashes = [];
        $seenVideoIds = [];

        foreach ($files as $file) {
            $name = (string) ($file['name'] ?? '');
            $sha256 = strtolower((string) ($file['sha256'] ?? ''));
            $filename = VideoFilename::tryParse($name);
            if ($filename === null) {
                $results[] = $this->rejected($name, 'INVALID_FILENAME');
                continue;
            }

            $parsed = [
                'external_drama_id' => $filename->externalDramaId,
                'external_video_id' => $filename->externalVideoId,
                'episode_no' => $filename->episodeNo,
                'object_key' => $filename->objectKey,
            ];

            if (isset($seenHashes[$sha256]) || isset($seenVideoIds[$filename->externalVideoId])) {
                $results[] = $this->rejected($name, 'DUPLICATE_IN_BATCH', $parsed);
                continue;
            }
            $seenHashes[$sha256] = true;
            $seenVideoIds[$filename->externalVideoId] = true;

            $drama = $this->dramas->findByExternalId($filename->externalDramaId);
            if ($drama === null) {
                $results[] = $this->rejected($name, 'DRAMA_NOT_FOUND', $parsed);
                continue;
            }
            if ($this->episodes->existsEpisodeNumber((int) $drama->getKey(), $filename->episodeNo)) {
                $results[] = $this->rejected($name, 'EPISODE_EXISTS', $parsed);
                continue;
            }
            if ($this->episodes->existsExternalVideoId($filename->externalVideoId)
                || $this->assets->existsObjectKey($filename->objectKey)) {
                $results[] = $this->rejected($name, 'VIDEO_ID_EXISTS', $parsed);
                continue;
            }
            if ($this->assets->existsSha256($sha256)) {
                $results[] = $this->rejected($name, 'HASH_EXISTS', $parsed);
                continue;
            }

            $results[] = [
                'name' => $name,
                'accepted' => true,
                'code' => null,
                'message' => null,
                ...$parsed,
            ];
        }

        return $results;
    }

    private function rejected(string $name, string $code, array $parsed = []): array
    {
        return [
            'name' => $name,
            'accepted' => false,
            'code' => $code,
            'message' => self::MESSAGES[$code],
            ...$parsed,
        ];
    }
}
