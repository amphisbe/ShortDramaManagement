# ShortDramaManagement Codex 会话交接档案

> 更新时间：2026-06-15（Asia/Shanghai）
> 用途：切换 Codex/OpenAI 账号或开启新会话后，先让新的 Codex 阅读本文件，再继续开发。
> 安全说明：本文只记录环境变量名称和配置状态，不保存 JWT、数据库密码或 Cloudflare R2 密钥。

## 1. 新会话启动提示词

在新 Codex 会话中发送：

```text
请先阅读 docs/CODEX-HANDOFF.md、
docs/superpowers/specs/2026-06-12-shortdrama-admin-design.md，
以及 docs/superpowers/plans 下的实施计划。
根据交接档案检查当前 Git、服务和数据库状态，然后继续完成短剧管理后台 MVP。
不要覆盖 README.md、SETUP.md、drama.sql 等未跟踪文件。
```

## 2. 项目位置和 Git 状态

- 项目目录：`D:\wangs\workspace\ShortDramaManagement`
- WSL 目录：`/mnt/d/wangs/workspace/ShortDramaManagement`
- Git 分支：`main`
- 当前 MVP 最新提交：`70cb13ef fix: finalize batch upload acceptance`
- 远程仓库：`git@github.com:amphisbe/ShortDramaManagement.git`
- 之前使用过的 worktree：`.worktrees/shortdrama-mvp`
- `codex/shortdrama-mvp` 的成果已经快进合并到当前 `main`

当前未跟踪文件：

- `README.md`
- `SETUP.md`
- `drama.sql`
- `web/src/plugins/shortdrama/`（本地开发发布副本）

这些文件不要随意删除或覆盖。其中 `drama.sql` 是短剧业务库建表文件，但目前未纳入 Git。

## 3. 项目目标

基于 MineAdmin 3 制作短剧运营管理后台，先交付最小 MVP，后续逐步扩展。

技术栈：

- 后端：PHP 8.1、Hyperf 3.1、Swoole 5、MineAdmin 3
- 前端：Vue 3、TypeScript、Vite 5、Element Plus
- 数据库：MySQL，系统库与短剧业务库双连接
- 缓存：Redis
- App 服务：Python/FastAPI
- 媒资：Cloudflare R2，双 Bucket

架构采用前后端分离：

- MineAdmin 管理后台负责运营管理。
- App 通过 Python 服务访问和修改业务数据。
- MineAdmin 与 Python 服务共享 `drama` MySQL 业务库。
- 视频和图片不经过 PHP 中转，浏览器使用预签名 URL 直传 R2。

## 4. 已确认的产品决策

### 4.1 MVP 模块

1. 数据看板
2. 短剧管理
3. 分集管理
4. 批量上传
5. App 用户管理
6. 数据导入

### 4.2 角色

只保留两个角色：

- 超级管理员：`SuperAdmin`
- 运营：`operations`

运营只获得 `shortdrama:*` 业务权限。

### 4.3 业务约束

- 不做评论需求。
- MVP 不提供删除短剧、删除分集功能。
- 不需要将历史视频回填到新媒资表。
- 总集数由运营手工填写。
- 页面进度格式为“已上传/总集数”，例如 `72/80`。
- 短剧状态：下架、连载、完结。
- 分集可进行状态编辑和批量状态操作。

### 4.4 UI

- 所有后台文字使用中文。
- 使用 MineAdmin 内部的 Element Plus 视觉和交互体系。
- 已确认当前高保真批量上传页面效果。
- 三张上传统计卡的数字必须水平、垂直居中。
- 页面进入后默认选择第一部短剧，因此可立即显示类似 `72/80` 的进度。

## 5. Cloudflare R2 与上传决策

使用两个 Bucket：

- 私有视频 Bucket：保存短剧 MP4。
- 公开图片 Bucket：保存封面、头像等图片，通过公开域名访问。

视频文件命名规则：

```text
{external_drama_id}_ep{两位集数}.mp4
```

示例：

```text
DRAMA001_ep01.mp4
```

规则含义：

- 短剧 ID：`external_drama_id`
- 视频 ID：`external_drama_id_ep01`
- `ep01` 表示第 01 集

批量上传流程：

1. 浏览器多文件选择。
2. 浏览器使用 Worker 增量计算 SHA-256。
3. 哈希计算并发数为 2。
4. 上传并发数为 3。
5. 调用批量预检接口检查文件名、短剧、集数、视频 ID、对象键和哈希重复。
6. 合法文件申请 R2 预签名 PUT URL。
7. 浏览器直接上传到私有视频 Bucket。
8. 上传完成后调用完成接口。
9. 后端 HEAD 校验对象大小，自动创建分集并将媒资标为 uploaded。
10. 单个文件失败不阻塞其他文件，可单独重试。

上传限制：

- 一次预检最多 200 个文件。
- 仅支持 MP4。
- 单文件最大 500MB，即 `524288000` 字节。
- SHA-256 和对象键会保存到 `media_assets`，用于防止重复上传。

## 6. 已实现代码

权威插件源码：

```text
plugin/shortdrama/
```

后端关键目录：

```text
plugin/shortdrama/src/Controller/
plugin/shortdrama/src/Service/
plugin/shortdrama/src/Repository/
plugin/shortdrama/src/Model/
plugin/shortdrama/src/Infrastructure/
plugin/shortdrama/src/Installer/
plugin/shortdrama/Database/
```

前端权威源码：

```text
plugin/shortdrama/web/
```

本地开发发布副本：

```text
web/src/plugins/shortdrama/admin/
```

注意：修改业务前端时优先修改 `plugin/shortdrama/web/`，再同步到本地发布副本。不要只修改生成副本，否则重新发布会丢失修改。

已实现后端能力：

- 短剧、分集、App 用户、看板和导入 API。
- 双数据库连接，业务模型使用 `drama` 连接。
- R2 S3 兼容客户端和对象存储抽象。
- `media_assets` 预约状态机：pending、uploaded、failed。
- 文件名解析与批量重复校验。
- 视频预签名、完成确认和幂等处理。
- 公开图片预签名上传。
- 两个角色、中文菜单和权限安装器。

已实现前端能力：

- 数据看板、短剧、分集、App 用户、数据导入页面。
- 中文高保真批量上传页面。
- Web Worker SHA-256。
- 多文件队列、失败隔离和单项重试。
- 上传进度和 `已上传/总集数` 展示。

## 7. API 前缀和主要接口

后台 API 前缀：

```text
/admin/shortdrama
```

主要媒资接口：

```text
POST /admin/shortdrama/media/check
POST /admin/shortdrama/media/presign
POST /admin/shortdrama/media/complete
POST /admin/shortdrama/images/upload/presign
```

看板接口示例：

```text
GET /admin/shortdrama/dashboard/overview
GET /admin/shortdrama/dashboard/ranking
GET /admin/shortdrama/dashboard/distribution
```

这些接口受 JWT 和权限中间件保护。未携带 Token 时返回 `401 未授权` 属于正常行为。

## 8. 关键提交记录

```text
70cb13ef fix: finalize batch upload acceptance
3e6e8022 feat: add high fidelity batch upload page
20bb6efb feat: add browser hash and upload queues
cb979f60 feat: add public R2 image uploads
395be77d feat: add idempotent direct video upload workflow
d57412ab feat: validate batch video names and duplicates
a7df1abf feat: add media asset reservation state machine
26d156c3 feat: add Cloudflare R2 storage adapter
837c5b42 feat: add short drama admin pages
5e95f835 feat: seed short drama roles and permissions
9641e5d1 feat: add users dashboard and metadata imports
32eaadeb feat: add drama and episode management APIs
bfd04eb3 feat: add short drama business models and repositories
522e6504 feat: add shortdrama plugin and business database
64b23f54 chore: bootstrap MineAdmin 3 foundation
```

## 9. 设计与实施文档

主要规格：

```text
docs/superpowers/specs/2026-06-12-shortdrama-admin-design.md
```

实施计划：

```text
docs/superpowers/plans/2026-06-14-mineadmin-business-foundation.md
docs/superpowers/plans/2026-06-14-r2-batch-upload.md
docs/superpowers/plans/2026-06-14-fastapi-compatibility.md
docs/superpowers/plans/2026-06-14-shortdrama-mvp-roadmap.md
```

FastAPI 兼容计划主要包含：

- 修正 App 可见状态和封面 URL。
- 为私有视频对象键生成签名 GET URL。
- 拒绝已禁用 App 用户。
- MineAdmin 与 Python 服务联调。

开始该部分前，应先检查计划中的任务是否已在 Python 仓库实现；当前项目目录主要是 MineAdmin 代码。

## 10. 环境变量

项目根目录使用 `.env`，前端使用：

```text
web/.env.development
web/.env.production
```

必须配置的后端变量：

```env
APP_NAME=MineAdmin
APP_ENV=dev
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mineadmin
DB_USERNAME=...
DB_PASSWORD=...

DRAMA_DB_HOST=127.0.0.1
DRAMA_DB_PORT=3306
DRAMA_DB_DATABASE=drama
DRAMA_DB_USERNAME=...
DRAMA_DB_PASSWORD=...

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_AUTH=

JWT_SECRET=<64字节随机内容的Base64编码>
JWT_TTL=3600
JWT_REFRESH_TTL=7200
JWT_BLACKLIST_TTL=7201

R2_ACCOUNT_ID=...
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_PUBLIC_BUCKET=...
R2_PRIVATE_BUCKET=...
R2_PUBLIC_BASE_URL=https://...
R2_PUT_EXPIRES=900
R2_GET_EXPIRES=1800
```

当前状态：

- `JWT_SECRET` 已配置且非空。
- R2 六个关键变量存在，但值仍为空，尚未进行真实 R2 PUT/HEAD 联调。
- 当前 `.env` 没有显式 `APP_DEBUG`。
- 当前数据库和 Redis 主机名仍可能是容器配置值，开发机应根据实际服务改为 `127.0.0.1`。

不要把 `.env` 或任何真实密钥提交到 Git。

## 11. JWT 故障记录

曾出现错误：

```text
Lcobucci\JWT\Signer\Key\InMemory::base64Encoded():
Argument #1 ($contents) must be of type string, null given
```

根因是 `.env` 缺少 `JWT_SECRET`。已在当前本地 `.env` 中补充随机 Base64 密钥。

如果重新拉取仓库或切换机器，需要重新配置：

```env
JWT_SECRET=<有效的Base64字符串>
```

## 12. MineAdmin 安装命令注意事项

当前 MineAdmin 快照没有 `mine:install` 命令。

执行：

```bash
php bin/hyperf.php mine:install
```

会被 Symfony 模糊匹配成：

```text
mine-extension:install
```

然后报：

```text
Not enough arguments (missing: "path")
```

短剧插件安装的正确命令是：

```bash
php bin/hyperf.php mine-extension:install shortdrama -y
```

参数使用实际插件目录 `shortdrama`，不是插件声明名 `shortdrama/admin`。

### install.lock 陷阱

`plugin/shortdrama/install.lock` 已被 Git 跟踪，而且 MineAdmin 仅通过“文件是否存在”判断插件是否安装。

因此：

- 保留锁文件：插件类会被加载，但安装器、菜单、角色、迁移和前端复制不会再次执行。
- 新环境首次安装：必须先删除锁文件，再执行安装命令。

```bash
rm -f plugin/shortdrama/install.lock
php bin/hyperf.php mine-extension:install shortdrama -y
```

插件安装需要 MineAdmin 系统库、`drama` 业务库和 Redis 可用。

## 13. 前端插件发布注意事项

MineAdmin 前端插件加载器要求两层目录：

```text
web/src/plugins/shortdrama/admin/index.ts
```

本地同步命令（PowerShell）：

```powershell
$target = 'web/src/plugins/shortdrama/admin'
Remove-Item $target -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path $target -Force | Out-Null
Copy-Item 'plugin/shortdrama/web/*' $target -Recurse -Force
```

在新环境中执行 `mine-extension:install shortdrama -y` 后，务必检查实际复制结果。如果生成的是：

```text
web/src/plugins/shortdrama/index.ts
```

则目录少了一层 `admin`，Vite 无法通过 `import.meta.glob('../../plugins/*/*/index.ts')` 发现插件，需要手动移动到上述正确路径。

## 14. 当前本地开发状态

2026-06-15 最后检查结果：

- 前端开发服务：`http://localhost:2888/`
- 后端服务：`http://localhost:9501/`
- 浏览器当前页面：`http://localhost:2888/#/login?redirect=/`
- MineAdmin 登录页已正常显示中文。
- 浏览器控制台无错误。
- 前端 HTTP 返回 200。
- 后端根地址返回 200。
- 短剧受保护接口返回 401，证明路由已注册。
- 后端日志：`.codex-backend.out.log`、`.codex-backend.err.log`
- 前端日志：`web/.codex-frontend.log`

启动方式：

```bash
# 后端（WSL）
cd /mnt/d/wangs/workspace/ShortDramaManagement
php bin/hyperf.php start

# 前端（PowerShell）
cd D:\wangs\workspace\ShortDramaManagement\web
$env:NODE_ENV='development'
corepack yarn dev --host 0.0.0.0
```

当前后端使用普通 `start`，不是 watcher。代码变更后需要重启后端；如果启用 `APP_DEBUG=true`，也应删除 `runtime/container` 后重启以刷新扫描缓存。

## 15. 当前环境阻塞项

本机检查时未发现：

- MySQL 3306 监听
- Redis 6379 监听
- Docker 命令
- WSL 内 MySQL/Redis 服务

所以当前虽然前后端进程能启动，但无法完成：

- 管理员登录
- MineAdmin 系统表初始化
- 短剧插件安装器写入菜单和角色
- `media_assets` 迁移
- 短剧 CRUD 和看板真实数据查询
- 真实 R2 上传联调

下一步应先安装或启动 MySQL、Redis，并创建：

```text
mineadmin
drama
```

`drama.sql` 可以用于创建现有业务表。`media_assets` 由短剧插件迁移创建。

## 16. 依赖、构建和测试

安装后端依赖：

```bash
cd /mnt/d/wangs/workspace/ShortDramaManagement
composer install
```

安装前端依赖：

```powershell
cd D:\wangs\workspace\ShortDramaManagement\web
$env:NODE_ENV='development'
corepack yarn install --frozen-lockfile
```

前端生产构建：

```powershell
$env:NODE_ENV='development'
corepack yarn build
```

最新验证结果：

- 前端生产构建成功，转换 5086 个模块。
- 短剧后端测试：24/24，通过，100 assertions。
- 短剧前端测试：14/14，通过。
- ESLint 和 Stylelint 在 MVP 分支验收时通过。
- 短剧插件 TypeScript 错误为 0。
- MineAdmin 全仓存在约 100 条既有 TypeScript 错误，不属于短剧插件改动。

短剧后端测试：

```bash
vendor/bin/co-phpunit --prepend tests/bootstrap.php tests/Feature/ShortDrama
```

前端测试：

```powershell
corepack yarn test:unit
```

注意：仓库的 `composer test` 脚本没有指定测试目录，在当前 PHPUnit 版本会打印帮助并退出。`composer test tests` 又可能长时间卡在迁移/环境阶段；验证短剧模块时使用上面的精确命令。

测试可能生成：

```text
storage/swagger/http.json
```

它是生成文件，不要提交。

构建可能自动修改：

```text
web/types/auto-imports.d.ts
```

如果只是生成器增加了无关类型且不属于任务，应恢复该文件，避免提交噪音。

## 17. Cloudflare R2 部署要求

创建：

- 私有视频桶，例如 `shortdrama-videos`
- 公开图片桶，例如 `shortdrama-images`

R2 CORS 至少允许后台前端域名：

- `PUT`
- `GET` 或 `HEAD`（根据实际访问方式）
- 请求头 `Content-Type`

图片桶绑定公开域名，并把域名填写到 `R2_PUBLIC_BASE_URL`。

真实联调必须验证：

1. 视频预签名 URL 可 PUT。
2. 请求 `Content-Type` 与签名一致。
3. 完成接口可以 HEAD 到对象。
4. 私有视频可生成签名 GET URL供 App 播放。
5. 公开图片 URL 可直接访问。
6. 重复 SHA-256 和重复对象键被拒绝。

## 18. 推荐后续顺序

1. 启动 MySQL 和 Redis。
2. 修正 `.env` 的数据库与 Redis 主机。
3. 初始化 MineAdmin 系统库。
4. 导入 `drama.sql` 或连接现有 Python 业务库。
5. 删除 `install.lock`，执行短剧插件安装。
6. 确认菜单、两个角色和 `media_assets` 表。
7. 登录 MineAdmin，逐页验收 MVP。
8. 配置 Cloudflare R2 六个变量并做真实直传测试。
9. 按 FastAPI 兼容计划联调 App 用户状态、封面 URL和私有视频播放 URL。
10. 决定是否将 `drama.sql`、README、SETUP 和本交接文件纳入 Git。

## 19. 完成定义

MVP 最终验收应满足：

- 超级管理员、运营两个角色可正常登录和授权。
- 运营只能看到和操作短剧业务菜单。
- 短剧、分集、App 用户、看板和导入页面可访问。
- 批量上传支持多文件、SHA-256、文件名检查、重复检查、失败隔离和重试。
- 上传成功后自动创建分集。
- 页面正确显示 `已上传/总集数`。
- 私有视频与公开图片分别进入两个 R2 Bucket。
- App 通过 Python 服务获得正确的业务状态和可播放视频 URL。
- 自动化测试、生产构建和关键浏览器流程通过。
