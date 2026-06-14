# 短剧管理后台 MVP 实施路线图

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 按最小可用范围交付 MineAdmin 管理后台、Cloudflare R2 双 Bucket 上传链路，以及 Python FastAPI 的读取兼容改造。

**Architecture:** MineAdmin 3.0 负责后台权限、运营页面和写入业务库；FastAPI 继续服务 App 并读取同一个 `drama` 数据库；Cloudflare R2 公有桶保存图片，私有桶保存视频。三个子计划按依赖顺序执行，各自可独立测试和提交。

**Tech Stack:** PHP 8.1+、Hyperf 3.1、MineAdmin 3.0、Vue 3、TypeScript、Element Plus、MaProTable、MySQL、Cloudflare R2、Python 3、FastAPI、Peewee、pytest

---

## 交付边界

- 设计基线：[2026-06-12-shortdrama-admin-design.md](../specs/2026-06-12-shortdrama-admin-design.md)
- MVP 包含：短剧、分集、批量上传、Excel 导入、App 用户只读管理、实时数据看板、两个后台角色。
- MVP 不包含：评论管理、历史视频回填、转码、审核工作流、趋势分析、复杂数据权限。
- 禁止删除短剧、分集和 App 用户；只允许状态变更。

## 执行顺序

1. [MineAdmin 基础业务计划](2026-06-14-mineadmin-business-foundation.md)
2. [R2 批量上传计划](2026-06-14-r2-batch-upload.md)
3. [FastAPI 兼容改造计划](2026-06-14-fastapi-compatibility.md)

第二份计划依赖第一份计划完成 MineAdmin 骨架、`drama` 第二数据库连接、短剧与分集模型。第三份计划可以在第二份计划后执行，确保私有 R2 对象键已经有稳定格式。

## 跨计划契约

### 状态值

| 对象 | 值 | 含义 |
|---|---:|---|
| `dramas.status` | 0 | 下架 |
| `dramas.status` | 1 | 连载中 |
| `dramas.status` | 2 | 已完结 |
| `drama_episodes.status` | 0 | 下架 |
| `drama_episodes.status` | 1 | 上架 |
| `users.status` | 0 | 禁用 |
| `users.status` | 1 | 正常 |

### 媒资键名

```text
public bucket: covers/{external_drama_id}/{uuid}.{ext}
private bucket: videos/{external_drama_id}/{external_video_id}.mp4
external_video_id: {external_drama_id}_ep{episode_no padded to 2 digits}
```

### API 前缀

```text
/admin/shortdrama/dashboard
/admin/shortdrama/dramas
/admin/shortdrama/episodes
/admin/shortdrama/media
/admin/shortdrama/users
/admin/shortdrama/imports
```

## 总体验收

- [ ] 超级管理员拥有全部 MVP 权限，运营不具备后台用户、角色和系统配置权限。
- [ ] 运营可以新增和编辑短剧、管理分集状态、批量上传视频、导入 Excel、禁用或恢复 App 用户。
- [ ] 上传页正确展示 `已上传/总集数`，例如 `72/80`，统计卡片数字水平和垂直居中。
- [ ] 重复集数、重复 `external_video_id`、重复 SHA-256 均被阻止，合法文件不受同批错误文件影响。
- [ ] App 能看到状态为 1 或 2 的短剧，图片使用公有 URL，视频使用短期签名 GET URL。
- [ ] PHP、前端和 Python 测试全部通过，且没有把 R2 密钥写入前端或 Git。

## 最终验证命令

```powershell
# MineAdmin 后端
composer test
composer analyse

# MineAdmin 前端
Set-Location web
yarn test:unit
yarn lint:tsc
yarn build

# FastAPI
Set-Location D:\wangs\workspace\python\ShortDramaServer_py
pytest -q
```

预期：所有命令退出码为 `0`，前端构建产出 `web/dist`，pytest 无失败用例。
