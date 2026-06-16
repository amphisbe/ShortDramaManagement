<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\DbConnection\Db;

final class CreateMediaAssetsTable extends Migration
{
    public function up(): void
    {
        Db::connection('drama')->getSchemaBuilder()->create('media_assets', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('episode_id')->nullable()->unique();
            $table->string('bucket', 128);
            $table->string('object_key', 512)->unique();
            $table->char('sha256', 64)->unique();
            $table->string('original_name', 255);
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type', 128);
            $table->string('status', 24)->index();
            $table->string('failure_reason', 500)->nullable();
            $table->dateTime('reservation_expires_at')->nullable()->index();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Db::connection('drama')->getSchemaBuilder()->dropIfExists('media_assets');
    }
}
