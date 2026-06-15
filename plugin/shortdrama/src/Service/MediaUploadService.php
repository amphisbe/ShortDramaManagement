<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Service;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use DateTimeImmutable;
use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Contract\EpisodeRepositoryInterface;
use Plugin\ShortDrama\Contract\MediaAssetRepositoryInterface;
use Plugin\ShortDrama\Contract\ObjectStorage;
use Plugin\ShortDrama\Domain\VideoFilename;
use Plugin\ShortDrama\Exception\MediaAssetReservationConflict;
use Plugin\ShortDrama\Infrastructure\R2StorageConfig;
use Plugin\ShortDrama\Model\Drama;
use Plugin\ShortDrama\Model\DramaEpisode;
use Plugin\ShortDrama\Model\MediaAsset;

final class MediaUploadService
{
    public function __construct(
        private readonly DramaRepositoryInterface $dramas,
        private readonly EpisodeRepositoryInterface $episodes,
        private readonly MediaAssetRepositoryInterface $assets,
        private readonly ObjectStorage $storage,
        private readonly R2StorageConfig $config,
    ) {
    }

    public function presign(array $file, int $uploadedBy): array
    {
        $filename = VideoFilename::tryParse((string) ($file['name'] ?? ''));
        if ($filename === null) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '视频文件名格式不正确');
        }

        $drama = $this->dramas->findByExternalId($filename->externalDramaId);
        if (! $drama instanceof Drama) {
            throw new BusinessException(ResultCode::NOT_FOUND, '短剧不存在');
        }
        if ($this->episodes->existsEpisodeNumber((int) $drama->getKey(), $filename->episodeNo)) {
            throw new BusinessException(ResultCode::CONFLICT, '该短剧集数已存在');
        }
        if ($this->episodes->existsExternalVideoId($filename->externalVideoId)) {
            throw new BusinessException(ResultCode::CONFLICT, '视频 ID 已存在');
        }

        try {
            $asset = $this->assets->reserve([
                'bucket' => $this->config->privateBucket,
                'object_key' => $filename->objectKey,
                'sha256' => strtolower((string) $file['sha256']),
                'original_name' => $filename->originalName,
                'size_bytes' => (int) $file['size'],
                'mime_type' => (string) $file['mime_type'],
                'reservation_expires_at' => (new DateTimeImmutable())
                    ->modify(sprintf('+%d seconds', $this->config->putExpires))
                    ->format('Y-m-d H:i:s'),
                'uploaded_by' => $uploadedBy,
            ]);
        } catch (MediaAssetReservationConflict) {
            throw new BusinessException(ResultCode::CONFLICT, '文件已被预占或上传');
        }

        return [
            'asset_id' => (int) $asset->getKey(),
            'upload_url' => $this->storage->presignPut(
                $this->config->privateBucket,
                $filename->objectKey,
                (string) $file['mime_type'],
                $this->config->putExpires,
            ),
            'object_key' => $filename->objectKey,
            'expires_in' => $this->config->putExpires,
        ];
    }

    public function complete(int $assetId): array
    {
        $outcome = $this->assets->transaction(function () use ($assetId): array {
            $asset = $this->assets->findByIdForUpdate($assetId);
            if (! $asset instanceof MediaAsset) {
                throw new BusinessException(ResultCode::NOT_FOUND, '媒体记录不存在');
            }

            if ($asset->status === MediaAsset::STATUS_UPLOADED && $asset->episode_id !== null) {
                $episode = $this->episodes->findById((int) $asset->episode_id);
                if (! $episode instanceof DramaEpisode) {
                    throw new BusinessException(ResultCode::FAIL, '媒体记录与分集数据不一致');
                }
                return ['episode' => $episode];
            }

            $metadata = $this->storage->head($asset->bucket, $asset->object_key);
            if ($metadata === null) {
                return $this->failedOutcome($asset, 'R2 对象不存在');
            }
            if ((int) ($metadata['content_length'] ?? -1) !== (int) $asset->size_bytes) {
                return $this->failedOutcome($asset, 'R2 对象大小与预检文件不一致');
            }

            $filename = VideoFilename::tryParse((string) $asset->original_name);
            if ($filename === null) {
                return $this->failedOutcome($asset, '媒体文件名无法解析');
            }
            $drama = $this->dramas->findByExternalId($filename->externalDramaId);
            if (! $drama instanceof Drama) {
                return $this->failedOutcome($asset, '短剧不存在');
            }
            if ($this->episodes->existsEpisodeNumber((int) $drama->getKey(), $filename->episodeNo)
                || $this->episodes->existsExternalVideoId($filename->externalVideoId)) {
                throw new BusinessException(ResultCode::CONFLICT, '分集或视频 ID 已存在');
            }

            $episode = $this->episodes->create($this->episodeAttributes($drama, $filename, $asset));
            $this->assets->markUploaded($asset, (int) $episode->getKey());

            return ['episode' => $episode];
        });

        if (isset($outcome['error'])) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, $outcome['error']);
        }

        return $outcome['episode']->toArray();
    }

    private function failedOutcome(MediaAsset $asset, string $reason): array
    {
        $this->assets->markFailed($asset, $reason);
        return ['error' => $reason];
    }

    private function episodeAttributes(Drama $drama, VideoFilename $filename, MediaAsset $asset): array
    {
        return [
            'drama_id' => (int) $drama->getKey(),
            'external_video_id' => $filename->externalVideoId,
            'episode_no' => $filename->episodeNo,
            'title' => sprintf('第 %02d 集', $filename->episodeNo),
            'play_url' => $asset->object_key,
            'poster_url' => (string) $drama->cover_url,
            'duration_seconds' => 0,
            'sort_order' => $filename->episodeNo,
            'status' => DramaEpisode::STATUS_ONLINE,
            'display_nickname' => (string) $drama->display_author_name,
            'loop' => 0,
            'play_ing' => 0,
            'muted' => 0,
            'is_playing' => 0,
            'show_title_arrow' => 1,
            'show_look_all_btn' => 1,
            'look_all_btn_text' => '查看全集',
            'show_bottom_area' => 1,
            'bottom_area_btn_text' => '立即观看',
            'tool_info_json' => '{}',
        ];
    }
}
