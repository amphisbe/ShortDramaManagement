# MineAdmin 管理后台 - 初始化指南

## 前置环境

确保本地已安装:
- PHP 8.x (推荐 8.1+)
- Composer 2.x
- Swoole 5.x 或 Swow (PHP 扩展)
- MySQL 8.0+
- Node.js 18+ 和 npm

## 一、安装 MineAdmin 框架

在项目根目录 `D:\wangs\workspace\ShortDramaManagement` 执行:

```bash
# 1. 通过 Composer 创建 MineAdmin v3 项目
composer create-project mineadmin/mineadmin .

# 2. 安装完成后，配置环境变量
cp .env.example .env
```

## 二、配置数据库连接

编辑 `.env` 文件:

```env
# MineAdmin 系统库（MineAdmin 核心表）
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mineadmin
DB_USERNAME=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# 短剧业务库（与 Python FastAPI 共享）
DRAMA_DB_HOST=127.0.0.1
DRAMA_DB_PORT=3306
DRAMA_DB_DATABASE=drama
DRAMA_DB_USERNAME=root
DRAMA_DB_PASSWORD=your_password
```

## 三、初始化数据库

```bash
# 1. 创建 drama 业务库并导入表结构
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS drama DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p drama < drama.sql

# 2. MineAdmin 自动创建系统表（首次启动时）
php bin/hyperf.php mine:install
```

## 四、启动开发服务

```bash
# 启动后端（Hyperf + Swoole）
php bin/hyperf.php start

# 另开终端，安装前端依赖并启动
cd web
npm install
npm run dev
```

## 五、访问

- 后端 API: http://127.0.0.1:9501
- 前端开发: http://127.0.0.1:5173
- 默认管理员: admin / admin123 (MineAdmin 初始账号)

## 六、创建 shortdrama 插件

MineAdmin 后台 → 插件管理 → 创建新插件

或命令行:
```bash
php bin/hyperf.php mine:gen plugin shortdrama
```

---

## 当前项目文件

| 文件 | 用途 |
|------|------|
| `drama.sql` | 短剧业务库建表语句（已导入 MySQL） |
| `docs/superpowers/specs/2026-06-12-shortdrama-admin-design.md` | 完整设计文档 |
