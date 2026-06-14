# FastAPI 短剧数据兼容 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 让现有 App 服务正确读取 MineAdmin 写入的新状态、公有图片 URL 和私有 R2 视频对象键，并拒绝被后台禁用的用户。

**Architecture:** 不改变 App API 的主响应结构；新增独立的 R2 签名服务和活跃用户守卫，在 service 层统一处理 URL 与状态。Peewee 继续读取共享 `drama` 数据库，MineAdmin 不调用 FastAPI 写入后台数据。

**Tech Stack:** Python 3、FastAPI、Peewee、boto3、pytest、httpx、Cloudflare R2

---

**Working directory:** `D:\wangs\workspace\python\ShortDramaServer_py`

## Task 1: 建立测试基线和 R2 配置

**Files:**
- Modify: `requirements.txt`
- Modify: `app/core/config.py`
- Create: `tests/conftest.py`
- Create: `tests/test_config.py`
- Create: `.env.example`

- [ ] **Step 1: 添加依赖和失败测试**

`requirements.txt` 增加 `boto3`。测试断言配置对象提供 `r2_account_id`、`r2_access_key_id`、`r2_secret_access_key`、`r2_private_bucket`、`r2_get_expires`。

```python
def test_r2_defaults(settings):
    assert settings.r2_private_bucket == "shortdrama-private"
    assert settings.r2_get_expires == 1800
```

- [ ] **Step 2: 运行测试确认失败**

```powershell
pytest tests/test_config.py -q
```

- [ ] **Step 3: 实现配置**

```python
r2_account_id: str = ""
r2_access_key_id: str = ""
r2_secret_access_key: str = ""
r2_private_bucket: str = "shortdrama-private"
r2_get_expires: int = 1800
```

- [ ] **Step 4: 复测并提交**

```powershell
pytest tests/test_config.py -q
git add requirements.txt app/core/config.py .env.example tests
git commit -m "feat: add FastAPI R2 configuration"
```

## Task 2: 修正短剧可见状态和封面 URL

**Files:**
- Modify: `app/services/drama_service.py`
- Test: `tests/test_drama_service.py`

- [ ] **Step 1: 写失败测试**

覆盖状态 1 和 2 均可见、状态 0 不可见；`cover_url` 为 HTTP(S) 时原样返回；为空时继续使用旧静态路径兼容历史数据。

```python
def test_completed_drama_is_visible(seed_drama):
    drama = seed_drama(status=2, cover_url="https://img.example/cover.webp")
    item = DramaService.get_drama_detail(drama.external_drama_id)
    assert item["cover"] == "https://img.example/cover.webp"
```

- [ ] **Step 2: 实现统一查询和封面解析**

```python
VISIBLE_DRAMA_STATUSES = (1, 2)

def resolve_cover_url(drama) -> str:
    if drama.cover_url and drama.cover_url.startswith(("http://", "https://")):
        return drama.cover_url
    return build_legacy_static_cover_url(drama)
```

将所有 `Drama.status == 1` 替换为 `Drama.status.in_(VISIBLE_DRAMA_STATUSES)`，不要替换分集状态规则。

- [ ] **Step 3: 测试并提交**

```powershell
pytest tests/test_drama_service.py -q
git add app/services/drama_service.py tests/test_drama_service.py
git commit -m "fix: expose serializing and completed dramas"
```

## Task 3: 为私有视频对象键生成签名 GET URL

**Files:**
- Create: `app/services/r2_service.py`
- Modify: `app/services/drama_service.py`
- Modify: `app/services/play_service.py`
- Test: `tests/test_r2_service.py`
- Test: `tests/test_play_service.py`

- [ ] **Step 1: 写 URL 解析测试**

```python
def test_http_play_url_is_unchanged(r2_service):
    assert r2_service.resolve_play_url("https://cdn.example/1.mp4") == "https://cdn.example/1.mp4"

def test_object_key_is_presigned(r2_service, s3_client):
    url = r2_service.resolve_play_url("videos/D1/D1_ep01.mp4")
    assert "X-Amz-Signature=" in url
```

- [ ] **Step 2: 实现 R2 服务**

```python
class R2Service:
    def resolve_play_url(self, value: str) -> str:
        if value.startswith(("http://", "https://")):
            return value
        return self.client.generate_presigned_url(
            "get_object",
            Params={"Bucket": settings.r2_private_bucket, "Key": value},
            ExpiresIn=settings.r2_get_expires,
        )
```

客户端 endpoint 为 `https://{account_id}.r2.cloudflarestorage.com`，region 使用 `auto`，签名版本使用 `s3v4`。

- [ ] **Step 3: 应用到全部播放响应**

`drama_service.py` 的剧集列表和详情、`play_service.py` 的播放接口都必须调用同一个 `resolve_play_url()`。数据库仍保存对象键，不把临时签名 URL 回写数据库。

- [ ] **Step 4: 测试并提交**

```powershell
pytest tests/test_r2_service.py tests/test_play_service.py tests/test_drama_service.py -q
git add app/services/r2_service.py app/services/drama_service.py app/services/play_service.py tests
git commit -m "feat: presign private R2 video playback"
```

## Task 4: 拒绝已禁用 App 用户

**Files:**
- Modify: `app/services/user_service.py`
- Modify: `app/api/user.py`
- Modify: `app/api/drama.py`
- Modify: `app/api/video.py`
- Create: `app/api/dependencies.py`
- Test: `tests/test_active_user_guard.py`

- [ ] **Step 1: 写失败测试**

正常用户可访问需要用户身份的接口；`status=0` 返回 HTTP 403；不存在用户返回 404。公开短剧列表若当前实现不要求用户身份，保持公开。

- [ ] **Step 2: 实现统一守卫**

```python
def require_active_user(external_user_id: str) -> User:
    user = User.get_or_none(User.external_user_id == external_user_id)
    if user is None:
        raise HTTPException(status_code=404, detail="用户不存在")
    if user.status != 1:
        raise HTTPException(status_code=403, detail="用户已被禁用")
    return user
```

需要用户身份的收藏、点赞、进度、用户资料和个性化播放接口通过 FastAPI `Depends` 调用此守卫。删除现有调试 `print()`。

- [ ] **Step 3: 测试并提交**

```powershell
pytest tests/test_active_user_guard.py -q
git add app/api app/services/user_service.py tests/test_active_user_guard.py
git commit -m "feat: enforce disabled App user status"
```

## Task 5: 回归测试和联调

- [ ] **Step 1: 运行完整测试**

```powershell
pytest -q
```

Expected: 无失败、错误或跳过的关键业务用例。

- [ ] **Step 2: 启动服务并调用关键接口**

```powershell
uvicorn app.main:app --host 127.0.0.1 --port 8000
```

验证一个连载中短剧、一个已完结短剧、一个公有 R2 封面、一个私有 R2 视频和一个禁用用户。签名 URL 可播放且过期时间符合配置。

- [ ] **Step 3: 检查安全边界**

```powershell
rg -n "R2_SECRET|secret_access_key|X-Amz-Signature" . -g '!*.pyc' -g '!.env'
```

Expected: 密钥只从环境读取；源码、测试快照和日志中没有真实密钥或持久化签名 URL。

- [ ] **Step 4: 提交联调修正**

仅在联调产生修正时提交：

```powershell
git add app tests requirements.txt .env.example
git commit -m "fix: complete FastAPI short drama compatibility"
```
