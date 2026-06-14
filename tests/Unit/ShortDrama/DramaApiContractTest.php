<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use PHPUnit\Framework\TestCase;
use Plugin\ShortDrama\Controller\DramaController;
use Plugin\ShortDrama\Request\BatchDramaStatusRequest;
use Plugin\ShortDrama\Request\DramaRequest;
use Plugin\ShortDrama\Service\DramaService;
use ReflectionClass;

final class DramaApiContractTest extends TestCase
{
    public function testDramaApiFilesExposeRequiredTypesAndNoDeleteApi(): void
    {
        foreach ([DramaController::class, DramaService::class, DramaRequest::class, BatchDramaStatusRequest::class] as $class) {
            self::assertTrue(class_exists($class));
        }

        $controller = new ReflectionClass(DramaController::class);
        self::assertSame('App\\Http\\Admin\\Controller\\AbstractController', $controller->getParentClass()?->getName());
        self::assertSame(['list', 'create', 'update', 'batchStatus'], array_values(array_intersect(
            ['list', 'create', 'update', 'batchStatus'],
            array_map(static fn ($method) => $method->getName(), $controller->getMethods())
        )));

        $source = file_get_contents($controller->getFileName());
        self::assertStringContainsString("path: '/admin/shortdrama/dramas'", $source);
        self::assertStringContainsString("path: '/admin/shortdrama/dramas/{id}'", $source);
        self::assertStringContainsString("path: '/admin/shortdrama/dramas/batch-status'", $source);
        self::assertSame(2, substr_count($source, "Permission(code: 'shortdrama:drama:update')"));
        self::assertStringNotContainsString('Annotation\\Delete', $source);
        self::assertStringNotContainsString('function delete', $source);
    }

    public function testDramaRequestsDeclareRequiredValidationContract(): void
    {
        $source = file_get_contents((new ReflectionClass(DramaRequest::class))->getFileName());
        foreach (['external_drama_id', 'title', 'display_author_name', 'author_user_id', 'total_episodes', 'cover_url', 'vip_free', 'status', 'description', 'category', 'tags'] as $field) {
            self::assertStringContainsString("'{$field}'", $source);
        }

        $batch = file_get_contents((new ReflectionClass(BatchDramaStatusRequest::class))->getFileName());
        self::assertStringContainsString("'ids.*' => 'integer|min:1|distinct'", $batch);
        self::assertStringContainsString("'status' => 'required|integer|in:0,1,2'", $batch);
    }
}
