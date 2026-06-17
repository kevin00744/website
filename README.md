# Laravel CMS — tsanyen.com 內容管理系統

## 技術堆疊

| 層級 | 技術 |
|------|------|
| 後端框架 | Laravel 11 |
| 資料庫 | PostgreSQL 16 |
| 快取 / 佇列 | Redis |
| 前端管理後台 | Vue 3 + Inertia.js |
| 頁面視覺編輯器 | GrapesJS |
| 前端建構 | Vite |
| 反向代理 | Nginx |
| 容器化 | Docker + Docker Compose |

---

## 快速啟動（全新環境 / 換機後）

### 1. 複製環境設定

```bash
cp .env.example .env
```

編輯 `.env`，至少填好以下欄位：

```
APP_KEY=          # 步驟 4 自動產生
DB_DATABASE=cms
DB_USERNAME=cms_user
DB_PASSWORD=secret
```

### 2. 啟動 Docker 容器

```bash
docker compose up -d
```

確認全部服務啟動（`app` / `nginx` / `db` / `redis` / `queue`）：

```bash
docker compose ps
```

### 3. 安裝後端與前端依賴

```bash
docker compose exec app composer install
docker compose exec app npm install
```

### 4. 初始化應用程式

```bash
# 產生 APP_KEY
docker compose exec app php artisan key:generate

# 執行資料庫 migration
docker compose exec app php artisan migrate

# 匯入 tsanyen.com 靜態網頁頁面（選用，僅首次需要）
docker compose exec app php artisan import:site-pages
```

### 5. 編譯前端資源

```bash
docker compose exec app npm run build
```

> **注意**：若 `public/hot` 檔案存在，需先刪除，否則頁面會白畫面：
> ```bash
> rm -f public/hot
> ```

### 6. 開啟瀏覽器

- 前台網站：`http://localhost:8080`
- 後台管理：`http://localhost:8080/admin`

---

## 帳號角色與權限

系統採四層角色階層：

| 角色 | 說明 | 主要權限 |
|------|------|----------|
| `admin` 管理員 | 最高權限 | 全部功能，含分店、商品、庫存管理 |
| `editor` 編輯 | 網站內容管理 | 網站文章、帳號管理（不含管理員帳號）；**無法**操作店務 |
| `manager` 店長 | 分店營運 | 管理本店店員帳號、調整本店庫存、發送補貨請求 |
| `staff` 店員 | 門市服務 | 查看本店庫存、新增顧客使用紀錄 |

### 建立第一個管理員帳號

```bash
docker compose exec app php artisan tinker --execute="
App\Models\User::create([
    'name'     => '管理員',
    'email'    => 'admin@example.com',
    'password' => bcrypt('your-password'),
    'role'     => 'admin',
]);
"
```

---

## 日常操作

### 重新編譯前端（修改 Vue/CSS 後）

```bash
docker compose exec app npm run build
```

### 停止 / 重啟服務

```bash
docker compose stop
docker compose up -d
```

### 查看 Laravel 錯誤日誌

```bash
docker compose exec app tail -f storage/logs/laravel.log
```

### 進入資料庫 psql

```bash
docker compose exec db psql -U cms_user -d cms
```

---

## 後台功能總覽

| 選單 | 路徑 | 可用角色 |
|------|------|----------|
| 文章管理 | `/admin/posts` | 全部 |
| 聯絡訊息 | `/admin/contacts` | 全部 |
| 顧客資料 | `/admin/customers` | 全部 |
| 庫存管理 | `/admin/inventory` | 管理員、店長、店員 |
| 商品目錄 | `/admin/products` | 管理員（新增/編輯）；其他角色可瀏覽 |
| 分店管理 | `/admin/stores` | 管理員 |
| 帳號管理 | `/admin/users` | 管理員、編輯、店長（限本店店員） |

---

## 專案結構（重點目錄）

```
cms/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/          # 後台控制器
│   │   └── Site/           # 前台公開頁面
│   ├── Models/             # Eloquent 模型
│   └── Support/SiteCss.php # 前台各頁面 CSS 對應表
├── database/migrations/    # 所有資料庫版本
├── resources/
│   ├── js/
│   │   ├── Pages/Admin/    # 後台 Vue 頁面
│   │   ├── Pages/Site/     # 前台 Vue 頁面
│   │   ├── Layouts/        # AdminLayout.vue
│   │   └── Components/
│   │       └── GrapesEditor.vue  # 拖拉視覺編輯器
│   └── views/
│       ├── app.blade.php   # 後台入口
│       └── site.blade.php  # 前台入口
├── routes/web.php
├── docker-compose.yml
└── docker/
    ├── nginx/
    └── php/Dockerfile
```

---

## WordPress 網站遷移

詳見 `MIGRATE.md`