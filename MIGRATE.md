# WordPress → Laravel CMS 遷移指南

## 遷移前準備

### 1. 備份 WordPress

```bash
# 備份資料庫
mysqldump -u root -p wordpress > wordpress_backup.sql

# 備份媒體檔案
cp -r /var/www/wordpress/wp-content/uploads ./wp-uploads-backup
```

### 2. 確認 WordPress 資料庫可被存取

新的 Laravel 應用需要能讀取 WordPress 的 MySQL 資料庫（只需讀取權限）。

---

## 遷移步驟

### 步驟一：啟動新系統

```bash
git clone https://github.com/yourname/cms.git
cd cms
cp .env.example .env
# 填寫 .env 中的資料庫設定（PostgreSQL）
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### 步驟二：執行遷移指令

```bash
docker compose exec app php artisan migrate:wordpress \
  --wp-host=your-mysql-host \
  --wp-db=wordpress \
  --wp-user=wp_user \
  --wp-password=your_password \
  --wp-prefix=wp_
```

加上 `--dry-run` 可先預覽，確認無誤再正式執行：

```bash
docker compose exec app php artisan migrate:wordpress \
  --wp-host=... --dry-run
```

### 步驟三：遷移媒體檔案

```bash
# 複製 WordPress 媒體到 Laravel storage
cp -r /path/to/wp-content/uploads/* storage/app/public/uploads/

# 更新 storage link
docker compose exec app php artisan storage:link
```

### 步驟四：重設用戶密碼

遷移的用戶密碼無法直接轉換（WordPress 使用不同的雜湊），需要讓用戶透過「忘記密碼」重設：

```bash
# 觸發全站密碼重設郵件（可選）
docker compose exec app php artisan users:send-password-reset
```

---

## 遷移對照表

| WordPress | Laravel CMS |
|-----------|-------------|
| `wp_posts` (post) | `posts` (type=post) |
| `wp_posts` (page) | `posts` (type=page) |
| `wp_users` | `users` |
| `wp_terms` (category) | `categories` |
| `wp_terms` (post_tag) | `tags` |
| `wp_options` | `settings` |
| `wp-content/uploads/` | `storage/app/public/uploads/` |

## 無法自動遷移的項目

| 項目 | 處理方式 |
|------|---------|
| WordPress 外掛功能 | 需手動在 Laravel 重新實作 |
| 佈景主題樣式 | 需用 Vue/Tailwind 重寫前端 |
| 評論 (comments) | 遷移腳本預設不遷移，可擴充 |
| 自訂欄位 (ACF) | 可加入 `post_meta` 欄位處理 |
| 用戶密碼 | 需重設 |

---

## URL 重定向（SEO 保護）

WordPress 的 URL 結構通常是 `/year/month/slug`，新系統預設是 `/blog/slug`。
在 Nginx 加入 301 重定向：

```nginx
# docker/nginx/default.conf 中加入
location ~ ^/(\d{4})/(\d{2})/(.+)$ {
    return 301 /blog/$3;
}
```

---

## 完成後的驗證清單

- [ ] 文章數量是否一致
- [ ] 分類 / 標籤是否完整
- [ ] 媒體圖片是否可正常顯示
- [ ] 管理員帳號可登入
- [ ] 公開 API `/api/v1/posts` 回傳正確資料
- [ ] SEO sitemap 已設定
- [ ] 舊 URL 已設定 301 重定向
