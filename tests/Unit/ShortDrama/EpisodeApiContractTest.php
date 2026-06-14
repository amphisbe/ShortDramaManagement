<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Controller\EpisodeController;
use Plugin\ShortDrama\Request\BatchEpisodeStatusRequest;
use Plugin\ShortDrama\Request\EpisodeRequest;
use Plugin\ShortDrama\Service\EpisodeService;
use ReflectionClass;

final class EpisodeApiContractTest extends TestCase
{
    public function testEpisodeApiFilesExposeRequiredTypesAndNoDeleteApi(): void
    {
        foreach ([EpisodeController::class, EpisodeService::class, EpisodeRequest::class, BatchEpisodeStatusRequest::class] as $class) {
            self::assertTrue(class_exists($class));
        }

        $controller = new ReflectionClass(EpisodeController::class);
        self::assertSame('App\\Http\\Admin\\Controller\\AbstractController', $controller->getParentClass()?->getName());
        $source = file_get_contents($controller->getFileName());
        self::assertStringContainsString("path: '/admin/shortdrama/episodes'", $source);
        self::assertStringContainsString("path: '/admin/shortdrama/episodes/{id}'", $source);
        self::assertStringContainsString("path: '/admin/shortdrama/episodes/batch-status'", $source);
        self::assertSame(3, substr_count($source, "Permission(code: 'shortdrama:episode:update')"));
        self::assertStringNotContainsString('Annotation\\Delete', $source);
        self::assertStringNotContainsString('function delete', $source);
    }

    public function testEpisodeRequestsDeclareRequiredValidationContract(): void
    {
        $source = file_get_contents((new ReflectionClass(EpisodeRequest::class))->getFileName());
        foreach (['drama_id', 'external_video_id', 'episode_no', 'title', 'play_url', 'poster_url', 'duration_seconds', 'sort_order', 'status', 'loop', 'play_ing', 'muted', 'is_playing', 'show_title_arrow', 'show_look_all_btn', 'show_bottom_area', 'tool_info_json'] as $field) {
            self::assertStringContainsString("'{$field}'", $source);
        }

        $batch = file_get_contents((new ReflectionClass(BatchEpisodeStatusRequest::class))->getFileName());
        self::assertStringContainsString("'ids.*' => 'integer|min:1|distinct'", $batch);
        self::assertStringContainsString("'status' => 'required|integer|in:0,1'", $batch);
    }
}
