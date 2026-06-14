# MineAdmin 短剧业务基础 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 在当前仓库落地 MineAdmin 3.0，并交付不含视频上传实现的短剧、分集、App 用户、Excel 导入和实时看板功能。

**Architecture:** MineAdmin 系统表使用默认数据库连接，短剧业务模型统一使用 `drama` 连接。业务代码封装为 `plugin/shortdrama` 混合插件，后端通过 Controller → Service → Repository → Model 分层，前端通过 MineAdmin 插件视图和 MaProTable 提供管理页面。

**Tech Stack:** MineAdmin 3.0、Hyperf 3.1、PHPUnit、MySQL、Vue 3、TypeScript、Element Plus、MaProTable、Vitest、PhpSpreadsheet

---

## Task 1: 安全引入 MineAdmin 3.0 基线

**Files:**
- Create: `app/`, `bin/`, `config/`, `databases/`, `plugin/`, `tests/`, `web/`
- Create: `composer.json`, `composer.lock`, `.env.example`, `phpunit.xml`, `phpstan.neon.dist`
- Modify: `.gitignore`
- Preserve: `docs/`, `drama.sql`, `README.md`, `SETUP.md`

- [ ] **Step 1: 确认源基线和目标文件**

```powershell
git -C D:\wangs\workspace\mineadmin rev-parse --show-toplevel
git status --short
```

Expected: 第一条输出 `D:/wangs/workspace/mineadmin`；目标仓库只显示已知的未跟踪资料文件。

- [ ] **Step 2: 从本地 MineAdmin 的已提交版本生成干净快照**

```powershell
$stage = Join-Path $env:TEMP 'shortdrama-mineadmin-bootstrap'
$archive = Join-Path $env:TEMP 'shortdrama-mineadmin.tar'
Remove-Item -LiteralPath $stage -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -LiteralPath $archive -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path $stage | Out-Null
git -C D:\wangs\workspace\mineadmin archive HEAD -o $archive
tar -xf $archive -C $stage
```

Expected: `$stage` 包含 MineAdmin 目录，但不包含 `.git`、`.env`、`vendor`、`node_modules` 和运行时文件。

- [ ] **Step 3: 只复制框架文件，保留当前资料文件**

```powershell
$items = @('app','bin','config','databases','plugin','storage','tests','web','composer.json','composer.lock','phpunit.xml','phpstan.neon.dist','.env.example')
foreach ($item in $items) {
    Copy-Item -LiteralPath (Join-Path $stage $item) -Destination . -Recurse -Force
}
```

- [ ] **Step 4: 合并 MineAdmin 忽略项**

在 `.gitignore` 保留现有 `.superpowers/`，并追加：

```gitignore
/.env
/vendor/
/runtime/
/web/node_modules/
/web/dist/
/web/.eslintcache
```

- [ ] **Step 5: 安装依赖并验证基线**

```powershell
composer install
Set-Location web
corepack enable
yarn install --immutable
yarn lint:tsc
```

Expected: Composer 和 TypeScript 均退出 `0`。

- [ ] **Step 6: 提交基线**

```powershell
git add app bin config databases plugin storage tests web composer.json composer.lock phpunit.xml phpstan.neon.dist .env.example .gitignore
git commit -m "chore: bootstrap MineAdmin 3 foundation"
```

## Task 2: 创建 shortdrama 插件和第二数据库连接

**Files:**
- Create: `plugin/shortdrama/mine.json`
- Create: `plugin/shortdrama/src/ConfigProvider.php`
- Create: `plugin/shortdrama/src/InstallScript.php`
- Create: `plugin/shortdrama/src/UninstallScript.php`
- Create: `plugin/shortdrama/install.lock`
- Modify: `config/autoload/databases.php`
- Modify: `.env.example`
- Test: `tests/Unit/ShortDrama/DatabaseConfigTest.php`

- [ ] **Step 1: 先写失败的数据库配置测试**

```php
<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ShortDrama;

use HyperfTests\TestCase;

final class DatabaseConfigTest extends TestCase
{
    public function testDramaConnectionExists(): void
    {
        $config = require BASE_PATH . '/config/autoload/databases.php';

        self::assertSame('mysql', $config['drama']['driver']);
        self::assertSame('drama', $config['drama']['database']);
    }
}
```

- [ ] **Step 2: 运行测试确认失败**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/ShortDrama/DatabaseConfigTest.php
```

Expected: 因不存在 `$config['drama']` 而失败。

- [ ] **Step 3: 增加 `drama` 连接**

在 `config/autoload/databases.php` 的返回数组中加入：

```php
'drama' => [
    'driver' => 'mysql',
    'host' => env('DRAMA_DB_HOST', '127.0.0.1'),
    'port' => (int) env('DRAMA_DB_PORT', 3306),
    'database' => env('DRAMA_DB_DATABASE', 'drama'),
    'username' => env('DRAMA_DB_USERNAME', 'root'),
    'password' => env('DRAMA_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'pool' => $defaultPool,
],
```

先把默认连接的 `pool` 数组抽为文件顶部的 `$defaultPool`，两个连接复用同一份值。

- [ ] **Step 4: 创建插件清单和扫描配置**

`mine.json` 使用名称 `shortdrama/admin`、类型 `mix`、命名空间 `Plugin\\ShortDrama\\`，安装脚本只创建菜单和角色，不删除业务数据。`ConfigProvider` 的 `annotations.scan.paths` 必须包含 `__DIR__`。

- [ ] **Step 5: 补齐环境变量并复测**

```dotenv
DRAMA_DB_HOST=127.0.0.1
DRAMA_DB_PORT=3306
DRAMA_DB_DATABASE=drama
DRAMA_DB_USERNAME=root
DRAMA_DB_PASSWORD=
```

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/ShortDrama/DatabaseConfigTest.php
```

Expected: `OK (1 test, 2 assertions)`。

- [ ] **Step 6: 提交**

```powershell
git add plugin/shortdrama config/autoload/databases.php .env.example tests/Unit/ShortDrama
git commit -m "feat: add shortdrama plugin and business database"
```

## Task 3: 建立业务模型、查询仓库和状态约束

**Files:**
- Create: `plugin/shortdrama/src/Model/Drama.php`
- Create: `plugin/shortdrama/src/Model/DramaEpisode.php`
- Create: `plugin/shortdrama/src/Model/AppUser.php`
- Create: `plugin/shortdrama/src/Model/DramaEpisodeStat.php`
- Create: `plugin/shortdrama/src/Repository/DramaRepository.php`
- Create: `plugin/shortdrama/src/Repository/EpisodeRepository.php`
- Create: `plugin/shortdrama/src/Repository/AppUserRepository.php`
- Test: `tests/Unit/ShortDrama/ModelContractTest.php`

- [ ] **Step 1: 写模型契约测试**

```php
public function testBusinessModelsUseDramaConnection(): void
{
    self::assertSame('drama', (new Drama())->getConnectionName());
    self::assertSame('drama', (new DramaEpisode())->getConnectionName());
    self::assertSame('drama', (new AppUser())->getConnectionName());
}

public function testDramaVisibilityStatusesAreStable(): void
{
    self::assertSame([1, 2], Drama::APP_VISIBLE_STATUSES);
}
```

- [ ] **Step 2: 运行测试确认类不存在**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/ShortDrama/ModelContractTest.php
```

- [ ] **Step 3: 实现模型公共约束**

每个业务模型设置：

```php
protected ?string $connection = 'drama';
public bool $timestamps = true;
```

`Drama` 定义 `APP_VISIBLE_STATUSES = [1, 2]`，并建立 `episodes()`；`DramaEpisode` 建立 `drama()` 和 `stats()`。字段必须与 `drama.sql` 完全一致，不引入软删除。

- [ ] **Step 4: 实现仓库过滤规则**

列表查询支持关键字、状态、分类和时间区间；短剧列表使用子查询统计 `uploaded_episodes`，返回：

```php
'episode_progress' => sprintf('%d/%d', $uploadedEpisodes, $drama->total_episodes),
```

仓库不提供 `delete`、`forceDelete` 或批量删除方法。

- [ ] **Step 5: 运行模型测试和静态分析**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Unit/ShortDrama/ModelContractTest.php
vendor/bin/phpstan analyse plugin/shortdrama/src/Model plugin/shortdrama/src/Repository --memory-limit 500M -l 5 -c phpstan.neon.dist
```

- [ ] **Step 6: 提交**

```powershell
git add plugin/shortdrama/src/Model plugin/shortdrama/src/Repository tests/Unit/ShortDrama/ModelContractTest.php
git commit -m "feat: add short drama business models and repositories"
```

## Task 4: 实现短剧和分集管理 API

**Files:**
- Create: `plugin/shortdrama/src/Service/DramaService.php`
- Create: `plugin/shortdrama/src/Service/EpisodeService.php`
- Create: `plugin/shortdrama/src/Controller/DramaController.php`
- Create: `plugin/shortdrama/src/Controller/EpisodeController.php`
- Create: `plugin/shortdrama/src/Request/DramaRequest.php`
- Create: `plugin/shortdrama/src/Request/EpisodeRequest.php`
- Test: `tests/Feature/ShortDrama/DramaControllerTest.php`
- Test: `tests/Feature/ShortDrama/EpisodeControllerTest.php`

- [ ] **Step 1: 写失败的权限和行为测试**

至少覆盖：无令牌 401、无权限拒绝、列表成功、新增成功、编辑成功、状态切换成功、删除路由 404、重复 `external_drama_id` 422、重复集数 422。

```php
public function testDeleteRouteDoesNotExist(): void
{
    $this->addPermissions('shortdrama:drama:update');
    $result = $this->delete('/admin/shortdrama/dramas/1', [], $this->authHeaders());
    self::assertSame(404, $result['code']);
}
```

- [ ] **Step 2: 实现路由和权限码**

```text
shortdrama:dashboard:view
shortdrama:drama:view
shortdrama:drama:create
shortdrama:drama:update
shortdrama:episode:view
shortdrama:episode:update
```

Controller 使用 `AccessTokenMiddleware`、`PermissionMiddleware`、`OperationMiddleware` 和 `#[Permission]`；只声明 GET、POST、PUT，不声明 DELETE。

- [ ] **Step 3: 实现事务和校验**

`DramaRequest` 将 `status` 限定为 `0,1,2`，`total_episodes >= 1`；`EpisodeRequest` 将 `status` 限定为 `0,1`。更新逻辑只允许白名单字段，批量状态更新放在 `Db::connection('drama')->transaction()` 内。

- [ ] **Step 4: 运行 Feature 测试**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Feature/ShortDrama/DramaControllerTest.php tests/Feature/ShortDrama/EpisodeControllerTest.php
```

Expected: 所有用例通过，且不存在删除接口。

- [ ] **Step 5: 提交**

```powershell
git add plugin/shortdrama/src/Controller plugin/shortdrama/src/Request plugin/shortdrama/src/Service tests/Feature/ShortDrama
git commit -m "feat: add drama and episode management APIs"
```

## Task 5: 实现 App 用户、实时看板和 Excel 导入

**Files:**
- Create: `plugin/shortdrama/src/Service/AppUserService.php`
- Create: `plugin/shortdrama/src/Service/DashboardService.php`
- Create: `plugin/shortdrama/src/Service/ImportService.php`
- Create: `plugin/shortdrama/src/Controller/AppUserController.php`
- Create: `plugin/shortdrama/src/Controller/DashboardController.php`
- Create: `plugin/shortdrama/src/Controller/ImportController.php`
- Test: `tests/Feature/ShortDrama/AppUserControllerTest.php`
- Test: `tests/Feature/ShortDrama/DashboardControllerTest.php`
- Test: `tests/Unit/ShortDrama/ImportServiceTest.php`
- Modify: `composer.json`, `composer.lock`

- [ ] **Step 1: 添加 Excel 解析依赖**

```powershell
composer require phpoffice/phpspreadsheet:^2.1
```

- [ ] **Step 2: 写失败测试**

用户测试确认资料字段不可编辑，只允许 `status` 在 0/1 间切换；看板测试确认返回短剧数、上架分集数、用户数、播放总数、分类分布和播放排行；导入测试确认同一文件中错误行不会阻止合法行提交。

```php
self::assertSame([
    'success_count' => 2,
    'failure_count' => 1,
], Arr::only($result, ['success_count', 'failure_count']));
```

- [ ] **Step 3: 实现只读用户管理**

用户列表返回 `external_user_id`、昵称、头像、状态、创建时间和行为统计。更新接口只接受：

```php
['status' => ['required', 'integer', 'in:0,1']]
```

- [ ] **Step 4: 实现实时聚合看板**

查询直接读取业务库，不落缓存，不生成趋势序列。所有计数使用 SQL 聚合，排行限制 10 条。

- [ ] **Step 5: 实现分行事务导入**

解析后先验证表头，再逐行执行短事务。结果结构固定为：

```php
[
    'success_count' => 0,
    'failure_count' => 0,
    'errors' => [['row' => 3, 'message' => 'external_drama_id 已存在']],
]
```

短剧模板字段为 `external_drama_id,title,display_author_name,author_user_id,total_episodes,cover_url,vip_free,status,description,category,tags`；分集模板字段与 `drama_episodes` 的可编辑字段一致。

- [ ] **Step 6: 运行测试并提交**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Feature/ShortDrama tests/Unit/ShortDrama/ImportServiceTest.php
git add composer.json composer.lock plugin/shortdrama/src tests/Feature/ShortDrama tests/Unit/ShortDrama/ImportServiceTest.php
git commit -m "feat: add users dashboard and metadata imports"
```

## Task 6: 安装两个角色和中文菜单

**Files:**
- Create: `plugin/shortdrama/Database/Seeders/ShortDramaMenuSeeder.php`
- Modify: `plugin/shortdrama/src/InstallScript.php`
- Test: `tests/Feature/ShortDrama/InstallScriptTest.php`

- [ ] **Step 1: 写失败的安装测试**

断言安装后只新增 `super_admin` 与 `operations` 两个目标角色，运营拥有业务权限但不拥有 `permission:user:*`、`permission:role:*` 和系统配置权限；重复执行安装不产生重复菜单或角色。

- [ ] **Step 2: 创建菜单树**

```text
短剧运营
├─ 数据看板
├─ 短剧管理
├─ 分集管理
├─ 批量上传
├─ App 用户
└─ 数据导入
```

菜单标题、按钮标题和 i18n 默认值全部使用中文。运营角色绑定全部 `shortdrama:*` MVP 权限；超级管理员保持全部菜单权限。

- [ ] **Step 3: 验证幂等性**

```powershell
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Feature/ShortDrama/InstallScriptTest.php
```

- [ ] **Step 4: 提交**

```powershell
git add plugin/shortdrama/Database plugin/shortdrama/src/InstallScript.php tests/Feature/ShortDrama/InstallScriptTest.php
git commit -m "feat: seed short drama roles and permissions"
```

## Task 7: 实现中文管理页面

**Files:**
- Create: `plugin/shortdrama/web/index.ts`
- Create: `plugin/shortdrama/web/api/drama.ts`
- Create: `plugin/shortdrama/web/api/episode.ts`
- Create: `plugin/shortdrama/web/api/user.ts`
- Create: `plugin/shortdrama/web/api/dashboard.ts`
- Create: `plugin/shortdrama/web/api/import.ts`
- Create: `plugin/shortdrama/web/views/dashboard/index.vue`
- Create: `plugin/shortdrama/web/views/drama/index.vue`
- Create: `plugin/shortdrama/web/views/drama/form.vue`
- Create: `plugin/shortdrama/web/views/episode/index.vue`
- Create: `plugin/shortdrama/web/views/user/index.vue`
- Create: `plugin/shortdrama/web/views/import/index.vue`
- Create: `web/vitest.config.ts`
- Test: `web/tests/shortdrama/episode-progress.spec.ts`
- Modify: `web/package.json`, `web/yarn.lock`

- [ ] **Step 1: 添加前端测试工具并写失败测试**

```powershell
Set-Location web
yarn add -D vitest@^2.1.9 @vue/test-utils@^2.4.6 jsdom@^25.0.1
```

```ts
import { describe, expect, it } from 'vitest'
import { formatEpisodeProgress } from '$/shortdrama/admin/utils/episode-progress'

describe('formatEpisodeProgress', () => {
  it('按已上传/总集数显示', () => {
    expect(formatEpisodeProgress(72, 80)).toBe('72/80')
  })
})
```

- [ ] **Step 2: 注册插件路由**

`plugin/shortdrama/web/index.ts` 注册六个中文页面，使用 MineAdmin 插件视图约定。执行插件安装后，文件应复制到 `web/src/plugins/shortdrama/admin/`。

- [ ] **Step 3: 实现高保真列表页**

全部列表使用 MaProTable；操作按钮使用 Element Plus；状态使用中文标签；删除按钮完全不渲染。短剧表格必须显示封面、标题、外部 ID、分类、状态、`已上传/总集数` 和更新时间。

- [ ] **Step 4: 实现表单、看板、用户和导入页**

看板只显示实时卡片、排行和分类分布；App 用户资料控件只读，仅状态按钮可操作；导入页支持下载模板、上传 Excel 和查看逐行结果。

- [ ] **Step 5: 校验中文和布局**

统计数字统一使用：

```scss
.metric-value {
  display: flex;
  min-height: 44px;
  align-items: center;
  justify-content: center;
  font-variant-numeric: tabular-nums;
}
```

页面可见文字不得残留英文占位或乱码。

- [ ] **Step 6: 测试、构建并提交**

```powershell
Set-Location web
yarn test:unit
yarn lint:tsc
yarn build
Set-Location ..
git add plugin/shortdrama/web web/package.json web/yarn.lock web/vitest.config.ts web/tests
git commit -m "feat: add Chinese short drama admin pages"
```

## Task 8: 基础业务总体验证

- [ ] **Step 1: 后端全量验证**

```powershell
composer test
vendor/bin/phpstan analyse plugin/shortdrama/src --memory-limit 500M -l 5 -c phpstan.neon.dist
```

- [ ] **Step 2: 前端全量验证**

```powershell
Set-Location web
yarn test:unit
yarn lint:tsc
yarn build
```

- [ ] **Step 3: 浏览器验收**

启动后端和前端，用 Codex Browser 逐页确认：中文菜单、两个角色权限、无删除入口、`72/80` 显示、数字居中、用户资料只读、Excel 部分成功结果。

- [ ] **Step 4: 提交验证修正**

仅在验证产生修正时提交：

```powershell
git add plugin/shortdrama tests web
git commit -m "fix: complete short drama foundation acceptance"
```
