<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Domain\VideoFilename;

final class VideoFilenameTest extends TestCase
{
    public static function filenames(): array
    {
        return [
            ['DRAMA001_ep01.mp4', true],
            ['drama_test-1_ep001.MP4', true],
            ['DRAMA001_ep1.mp4', false],
            ['DRAMA001_01.mp4', false],
            ['DRAMA001_ep01.mov', false],
            ['短剧001_ep01.mp4', false],
        ];
    }

    #[DataProvider('filenames')]
    public function testParsesOnlyApprovedVideoNames(string $name, bool $valid): void
    {
        self::assertSame($valid, VideoFilename::tryParse($name) !== null);
    }

    public function testBuildsVideoIdEpisodeNumberAndObjectKey(): void
    {
        $filename = VideoFilename::tryParse('DRAMA001_ep002.mp4');

        self::assertNotNull($filename);
        self::assertSame('DRAMA001', $filename->externalDramaId);
        self::assertSame('DRAMA001_ep002', $filename->externalVideoId);
        self::assertSame(2, $filename->episodeNo);
        self::assertSame('videos/DRAMA001/DRAMA001_ep002.mp4', $filename->objectKey);
    }
}
