<?php

declare(strict_types=1);

namespace Plugin\ShortDrama\Service;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use Plugin\ShortDrama\Contract\DramaRepositoryInterface;
use Plugin\ShortDrama\Contract\ObjectStorage;
use Plugin\ShortDrama\Infrastructure\R2StorageConfig;

final class ImageUploadService
{
    private const MAX_SIZE = 10 * 1024 * 1024;

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly DramaRepositoryInterface $dramas,
        private readonly ObjectStorage $storage,
        private readonly R2StorageConfig $config,
    ) {
    }

    public function presign(array $input): array
    {
        $externalDramaId = (string) ($input['external_drama_id'] ?? '');
        $size = (int) ($input['size'] ?? 0);
        $mimeType = (string) ($input['mime_type'] ?? '');

        if (preg_match('/^[A-Za-z0-9_-]+$/', $externalDramaId) !== 1) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '短剧 ID 格式不正确');
        }
        if (! isset(self::EXTENSIONS[$mimeType])) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '仅支持 JPEG、PNG、WebP 图片');
        }
        if ($size < 1 || $size > self::MAX_SIZE) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '图片大小不能超过 10 MB');
        }
        if ($this->dramas->findByExternalId($externalDramaId) === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '短剧不存在');
        }

        $objectKey = sprintf(
            'covers/%s/%s.%s',
            $externalDramaId,
            bin2hex(random_bytes(16)),
            self::EXTENSIONS[$mimeType],
        );

        return [
            'upload_url' => $this->storage->presignPut(
                $this->config->publicBucket,
                $objectKey,
                $mimeType,
                $this->config->putExpires,
            ),
            'public_url' => $this->config->publicBaseUrl . '/' . $objectKey,
            'expires_in' => $this->config->putExpires,
        ];
    }
}
