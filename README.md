# MineAdmin 管理后台

基于 MineAdmin v3.0.9 框架的短剧管理后台，与 Python FastAPI 数据源服务共享 MySQL 数据库。

## 技术栈

- 后端: Hyperf 3 + PHP 8 + Swoole
- 前端: Vue 3 + Vite 5 + TypeScript + Element Plus
- 数据库: MySQL 8.0+

## 快速开始

```bash
# 1. 安装框架
composer create-project mineadmin/mineadmin .

# 2. 配置环境
cp .env.example .env
# 编辑 .env 配置数据库连接

# 3. 导入业务表
mysql -u root -p drama < drama.sql

# 4. 安装 MineAdmin 系统表
php bin/hyperf.php mine:install

# 5. 启动
php bin/hyperf.php start
cd web && npm install && npm run dev
```

## 目录结构

```
ShortDramaManagement/
├── app/                  # MineAdmin 后端应用
├── plugin/               # 业务插件（shortdrama 插件将在这里）
├── web/                  # 前端应用
├── config/               # 配置文件
├── drama.sql             # 业务库建表语句
├── docs/                 # 设计文档
│   └── superpowers/specs/
│       └── 2026-06-12-shortdrama-admin-design.md
└── SETUP.md              # 详细安装指南
```

## 设计文档

完整设计见 [docs/superpowers/specs/2026-06-12-shortdrama-admin-design.md](docs/superpowers/specs/2026-06-12-shortdrama-admin-design.md)
