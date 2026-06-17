# Laravel CMS — 自製內容管理系統

## 技術堆疊

| 層級 | 技術 |
|------|------|
| 後端框架 | Laravel 11 |
| 資料庫 | PostgreSQL 16 |
| 快取 / Queue | Redis |
| 前端管理後台 | Vue 3 + Inertia.js |
| 富文本編輯器 | TipTap |
| 檔案儲存 | 本地 / S3 相容 |
| 反向代理 | Nginx |
| 容器化 | Docker + Docker Compose |
| CI/CD | GitHub Actions |

## 專案結構

```
cms/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── PostController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── MediaController.php
│   │   │   │   └── UserController.php
│   │   │   └── Api/
│   │   │       └── PostApiController.php
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php
│   │   └── Requests/
│   │       ├── StorePostRequest.php
│   │       └── StoreUserRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Post.php
│   │   ├── Category.php
│   │   ├── Tag.php
│   │   └── Media.php
│   └── Services/
│       ├── PostService.php
│       └── MediaService.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── web.php
│   └── api.php
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile
├── docker-compose.yml
└── .github/workflows/deploy.yml
```

## 快速啟動

```bash
# 1. 複製並設定環境
cp .env.example .env

# 2. 啟動 Docker
docker compose up -d

# 3. 安裝依賴
docker compose exec app composer install
docker compose exec app npm install

# 4. 初始化資料庫
docker compose exec app php artisan migrate --seed

# 5. 產生 key
docker compose exec app php artisan key:generate

# 6. 編譯前端
docker compose exec app npm run build
```

預設管理員帳號：`admin@example.com` / `password`

## WordPress 遷移

詳見 `MIGRATE.md`
