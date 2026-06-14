# Cloudflare R2 批量视频上传 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 为短剧后台提供双 Bucket 图片与视频上传，其中视频支持多文件、文件名校验、SHA-256 去重、并发上传、预占位和幂等完成。

**Architecture:** 浏览器在 Web Worker 中串行或双并发计算 SHA-256，MineAdmin 后端负责业务校验并签发 R2 预签名 PUT；浏览器直传私有桶后调用完成接口，后端通过 HEAD 校验对象并在事务中创建分集和媒资记录。图片由后端签发公有桶上传 URL，数据库只保存公开 URL。

**Tech Stack:** Cloudflare R2 S3 API、AWS SDK for PHP、Hyperf、MySQL、Vue 3、Web Worker、TypeScript、Vitest

---

## Task 1: 增加 R2 配置和可替换的对象存储接口

**Files:**
- Modify: `composer.json`, `composer.lock`, `.env.example`
- Create: `plugin/shortdrama/src/Contract/ObjectStorage.php`
- Create: `plugin/shortdrama/src/Infrastructure/R2ObjectStorage.php`
- Create: `plugin/shortdrama/src/Infrastructure/R2ClientFactory.php`
- Modify: `plugin/shortdrama/src/ConfigProvider.php`
- Test: `tests/Unit/ShortDrama/R2ObjectStorageTest.php`

- [ ] **Step 1: 安装 SDK**

```powershell
composer require aws/aws-sdk-php:^3.337
```

- [ ] **Step 2: 写失败的存储契约测试**

测试 `presignPut()`、`presignGet()`、`head()` 和 `delete()`；单元测试绑定 `FakeObjectStorage`，不得访问真实 R2。

```php
interface ObjectStorage
{
    public function presignPut(string $bucket, string $key, string $contentType, int $expiresIn): string;
    public function presignGet(string $bucket, string $key, int $expiresIn): string;
    public function head(string $bucket, string $key): ?array;
    public function delete(string $bucket, string $key): void;
}
```

- [ ] **Step 3: 配置 R2 客户端**

```php
new S3Client([
    'version' => 'latest',
    'region' => 'auto',
    'endpoint' => sprintf('https://%s.r2.cloudflarestorage.com', $accountId),
    'use_path_style_endpoint' => true,
    'credentials' => ['key' => $accessKeyId, 'secret' => $secretAccessKey],
]);
```

`.env.example` 增加 `R2_ACCOUNT_ID`、`R2_ACCESS_KEY_ID`、`R2_SECRET_ACCESS_KEY`、`R2_PUBLIC_BUCKET`、`R2_PRIVATE_BUCKET`、`R2_PUBLIC_BASE_URL`、`R2_PUT_EXPIRES=900`、`R2_GET_EXPIRES=1800`。

- [ ] **Step 4: 运行测试并提交**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/ShortDrama/R2ObjectStorageTest.php
git add composer.json composer.lock .env.example plugin/shortdrama/src/Contract plugin/shortdrama/src/Infrastructure plugin/shortdrama/src/ConfigProvider.php tests/Unit/ShortDrama/R2ObjectStorageTest.php
git commit -m "feat: add Cloudflare R2 storage adapter"
```

## Task 2: 创建 `media_assets` 状态机

**Files:**
- Create: `plugin/shortdrama/Database/Migrations/2026_06_14_000001_create_media_assets_table.php`
- Create: `plugin/shortdrama/src/Model/MediaAsset.php`
- Create: `plugin/shortdrama/src/Repository/MediaAssetRepository.php`
- Test: `tests/Feature/ShortDrama/MediaAssetMigrationTest.php`

- [ ] **Step 1: 写迁移测试**

断言表位于 `drama` 连接，`episode_id` 可空且唯一，`object_key` 和 `sha256` 唯一，并含 `status`、`failure_reason`、`reservation_expires_at`、`uploaded_by` 和时间戳。

- [ ] **Step 2: 创建迁移**

```php
Schema::connection('drama')->create('media_assets', static function (Blueprint $table): void {
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
```

状态仅允许 `pending`、`uploaded`、`failed`。不要导入或回填历史视频。

- [ ] **Step 3: 实现原子预占位**

Repository 提供 `reserve()`，在唯一键冲突时读取已有记录：未过期 `pending` 返回冲突；过期且 R2 无对象时允许重置；`uploaded` 永远拒绝。

- [ ] **Step 4: 测试并提交**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Feature/ShortDrama/MediaAssetMigrationTest.php
git add plugin/shortdrama/Database/Migrations plugin/shortdrama/src/Model/MediaAsset.php plugin/shortdrama/src/Repository/MediaAssetRepository.php tests/Feature/ShortDrama/MediaAssetMigrationTest.php
git commit -m "feat: add media asset reservation state machine"
```

## Task 3: 实现文件名解析和批量预检

**Files:**
- Create: `plugin/shortdrama/src/Domain/VideoFilename.php`
- Create: `plugin/shortdrama/src/Service/MediaValidationService.php`
- Create: `plugin/shortdrama/src/Controller/MediaController.php`
- Create: `plugin/shortdrama/src/Request/MediaCheckRequest.php`
- Test: `tests/Unit/ShortDrama/VideoFilenameTest.php`
- Test: `tests/Feature/ShortDrama/MediaCheckControllerTest.php`

- [ ] **Step 1: 写参数化解析测试**

```php
#[DataProvider('filenames')]
public function testParse(string $name, bool $valid): void
{
    self::assertSame($valid, VideoFilename::tryParse($name) !== null);
}

public static function filenames(): array
{
    return [
        ['DRAMA001_ep01.mp4', true],
        ['DRAMA001_ep1.mp4', false],
        ['DRAMA001_01.mp4', false],
        ['DRAMA001_ep01.mov', false],
    ];
}
```

- [ ] **Step 2: 实现严格格式**

使用正则：

```php
/^(?<drama>[A-Za-z0-9_-]+)_ep(?<episode>\d{2,})\.mp4$/i
```

解析结果必须生成 `external_video_id`、整数集数和 `videos/{external_drama_id}/{external_video_id}.mp4`。

- [ ] **Step 3: 实现 `POST /admin/shortdrama/media/check`**

请求每项包含 `name,size,mime_type,sha256`。响应逐文件返回 `accepted` 或错误码：`INVALID_FILENAME`、`DRAMA_NOT_FOUND`、`EPISODE_EXISTS`、`VIDEO_ID_EXISTS`、`HASH_EXISTS`、`DUPLICATE_IN_BATCH`。同批合法项保持可继续处理。

- [ ] **Step 4: 测试并提交**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/ShortDrama/VideoFilenameTest.php tests/Feature/ShortDrama/MediaCheckControllerTest.php
git add plugin/shortdrama/src/Domain plugin/shortdrama/src/Service/MediaValidationService.php plugin/shortdrama/src/Controller/MediaController.php plugin/shortdrama/src/Request/MediaCheckRequest.php tests/Unit/ShortDrama/VideoFilenameTest.php tests/Feature/ShortDrama/MediaCheckControllerTest.php
git commit -m "feat: validate batch video names and duplicates"
```

## Task 4: 实现预签名、完成和过期回收接口

**Files:**
- Create: `plugin/shortdrama/src/Service/MediaUploadService.php`
- Create: `plugin/shortdrama/src/Request/MediaPresignRequest.php`
- Create: `plugin/shortdrama/src/Request/MediaCompleteRequest.php`
- Modify: `plugin/shortdrama/src/Controller/MediaController.php`
- Test: `tests/Feature/ShortDrama/MediaUploadControllerTest.php`

- [ ] **Step 1: 写失败的完整流程测试**

覆盖预占位成功、重复预占位 409、过期预占位回收、HEAD 不存在、大小不一致、完成成功、重复完成返回同一结果，以及两个并发请求只有一个成功。

- [ ] **Step 2: 实现 presign 接口**

`POST /media/presign` 必须在事务中再次执行三重唯一校验并创建 `pending`，然后返回：

```json
{
  "asset_id": 123,
  "upload_url": "https://presigned-upload.example.com/videos/DRAMA001/DRAMA001_ep01.mp4",
  "object_key": "videos/DRAMA001/DRAMA001_ep01.mp4",
  "expires_in": 900
}
```

- [ ] **Step 3: 实现 complete 接口**

完成时锁定媒资行，HEAD 私有桶对象，验证 `ContentLength`，再创建分集：标题为 `第{episode_no}集`，`status=1`，`poster_url=drama.cover_url`，`play_url=object_key`，`sort_order=episode_no`，播放器布尔字段使用设计规格默认值。最后写入 `episode_id` 并置为 `uploaded`。

- [ ] **Step 4: 实现幂等和回收**

已完成记录再次 complete 直接返回原 `episode_id`；过期 `pending` 只有在 HEAD 不存在时才能重置；对象存在但业务未完成时保留记录并允许继续 complete。

- [ ] **Step 5: 测试并提交**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Feature/ShortDrama/MediaUploadControllerTest.php
git add plugin/shortdrama/src/Service/MediaUploadService.php plugin/shortdrama/src/Request plugin/shortdrama/src/Controller/MediaController.php tests/Feature/ShortDrama/MediaUploadControllerTest.php
git commit -m "feat: add idempotent direct video upload workflow"
```

## Task 5: 实现公有图片上传

**Files:**
- Create: `plugin/shortdrama/src/Service/ImageUploadService.php`
- Create: `plugin/shortdrama/src/Request/ImagePresignRequest.php`
- Modify: `plugin/shortdrama/src/Controller/MediaController.php`
- Test: `tests/Feature/ShortDrama/ImageUploadControllerTest.php`

- [ ] **Step 1: 写失败测试**

只允许 JPEG、PNG、WebP；限制大小；对象键必须位于 `covers/{external_drama_id}/`；返回值必须是 `R2_PUBLIC_BASE_URL` 下的公开 URL。

- [ ] **Step 2: 实现接口**

图片不写入 `media_assets`。接口返回 `upload_url`、`public_url`、`expires_in`，前端 PUT 成功后把 `public_url` 写入短剧 `cover_url` 或分集 `poster_url`。

- [ ] **Step 3: 测试并提交**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Feature/ShortDrama/ImageUploadControllerTest.php
git add plugin/shortdrama/src/Service/ImageUploadService.php plugin/shortdrama/src/Request/ImagePresignRequest.php plugin/shortdrama/src/Controller/MediaController.php tests/Feature/ShortDrama/ImageUploadControllerTest.php
git commit -m "feat: add public R2 image uploads"
```

## Task 6: 实现浏览器增量哈希和上传队列

**Files:**
- Create: `plugin/shortdrama/web/workers/sha256.worker.ts`
- Create: `plugin/shortdrama/web/composables/useBatchUpload.ts`
- Create: `plugin/shortdrama/web/api/media.ts`
- Create: `plugin/shortdrama/web/types/media.ts`
- Test: `web/tests/shortdrama/batch-upload.spec.ts`
- Modify: `web/package.json`, `web/yarn.lock`

- [ ] **Step 1: 添加增量哈希库并写队列测试**

```powershell
Set-Location web
yarn add hash-wasm@^4.12.0
```

测试要求哈希并发不超过 2、上传并发不超过 3、单文件失败不取消其他任务、同批重复哈希在调用后端前标错。

- [ ] **Step 2: 实现 Worker 分块哈希**

```ts
const chunkSize = 4 * 1024 * 1024
for (let offset = 0; offset < file.size; offset += chunkSize) {
  hasher.update(new Uint8Array(await file.slice(offset, offset + chunkSize).arrayBuffer()))
  postMessage({ type: 'progress', loaded: Math.min(offset + chunkSize, file.size), total: file.size })
}
postMessage({ type: 'done', sha256: hasher.digest('hex') })
```

- [ ] **Step 3: 实现队列状态**

状态固定为 `waiting_hash`、`hashing`、`checking`、`ready`、`uploading`、`completing`、`success`、`failed`。失败项保留中文原因并支持只重试该项。

- [ ] **Step 4: 测试并提交**

```powershell
Set-Location web
yarn test:unit --run batch-upload
Set-Location ..
git add plugin/shortdrama/web web/package.json web/yarn.lock web/tests/shortdrama/batch-upload.spec.ts
git commit -m "feat: add browser hash and upload queues"
```

## Task 7: 实现高保真批量上传页面

**Files:**
- Create: `plugin/shortdrama/web/views/upload/index.vue`
- Create: `plugin/shortdrama/web/views/upload/upload-table.vue`
- Create: `plugin/shortdrama/web/views/upload/upload-summary.vue`
- Modify: `plugin/shortdrama/web/index.ts`
- Test: `web/tests/shortdrama/upload-view.spec.ts`

- [ ] **Step 1: 写组件行为测试**

验证多选文件、删除等待项、只上传合法项、失败后重试、统计卡显示 `72/80`、本次文件数和上传并发数，数字水平与垂直居中。

- [ ] **Step 2: 实现页面**

使用 Element Plus Upload 的手动模式，不让组件直接上传。页面流程为：选择短剧 → 选择多个 MP4 → 计算哈希 → 批量预检 → 上传合法项 → 完成入库 → 刷新 `已上传/总集数`。

- [ ] **Step 3: 实现中文错误呈现**

错误码映射为稳定中文文案；一行失败不得弹出阻断整个批次的模态框。顶部摘要、逐文件状态和最终汇总保持一致。

- [ ] **Step 4: 测试、构建并提交**

```powershell
Set-Location web
yarn test:unit
yarn lint:tsc
yarn build
Set-Location ..
git add plugin/shortdrama/web web/tests
git commit -m "feat: add high fidelity batch upload page"
```

## Task 8: R2 联调和验收

- [ ] **Step 1: 配置 CORS**

私有桶允许管理后台域名执行 `PUT`、`HEAD`，允许请求头 `Content-Type`；公有桶允许图片 GET。不得使用生产环境的全域 `*` 写权限。

- [ ] **Step 2: 真实对象联调**

上传至少 8 个小 MP4，混入非法文件名、重复集数和重复内容。确认合法文件全部完成，失败项原因准确，数据库和 R2 对象一致。

- [ ] **Step 3: 过期和幂等验收**

模拟 PUT 成功但 complete 超时，再调用 complete；模拟预占位过期且无对象，再次上传。两种情况都不得产生重复分集。

- [ ] **Step 4: 全量验证**

```powershell
composer test
Set-Location web
yarn test:unit
yarn lint:tsc
yarn build
```

Expected: 全部退出 `0`，R2 密钥只存在于后端环境变量。
