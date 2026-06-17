# 從 wget 靜態檔案遷移到 Laravel CMS

## 流程總覽

```
wget 靜態資料夾
      │
      ▼
① analyze-wget.php     ← 先分析，確認檔案結構
      │
      ▼
② import:static-wordpress --dry-run   ← 模擬匯入，預覽結果
      │
      ▼
③ import:static-wordpress             ← 正式匯入
      │
      ▼
④ 人工審查後台                         ← 在 /admin 確認內容
```

---

## 步驟一：確認你的 wget 目錄結構

wget 爬下來的目錄通常長這樣（兩種常見結構）：

**結構 A（依日期）**
```
example.com/
├── 2023/
│   ├── 01/
│   │   └── my-first-post/
│   │       ├── index.html       ← 文章頁
│   │       └── featured.jpg
│   └── 02/
│       └── second-post/
│           └── index.html
├── wp-content/
│   └── uploads/
│       └── 2023/01/image.jpg   ← 媒體圖片
└── index.html                  ← 首頁
```

**結構 B（扁平化）**
```
example.com/
├── my-first-post.html
├── second-post.html
└── wp-content/uploads/...
```

---

## 步驟二：安裝依賴套件

在 Laravel 專案目錄執行：

```bash
composer require symfony/dom-crawler symfony/css-selector
```

---

## 步驟三：放入 Artisan 指令

把 `ImportStaticWordPress.php` 複製到 Laravel 專案：

```bash
cp ImportStaticWordPress.php app/Console/Commands/ImportStaticWordPress.php
```

在 `app/Console/Kernel.php` 或讓 Laravel 自動發現（Laravel 11 預設自動發現）。

---

## 步驟四：執行分析腳本（不需要 Laravel）

```bash
php analyze-wget.php /path/to/example.com
```

輸出範例：
```
📂 分析目錄：/var/www/example.com

📄 HTML 檔案：247 個
🖼  圖片檔案：512 個
💾 總大小：  143.2 MB

✅ 偵測到文章頁面：134 篇
⏭  非文章頁面：113 個

📅 文章日期範圍：2019-03-12 ～ 2024-11-05

📁 偵測到的分類（前 10 個）：
   技術 (42 篇)
   設計 (28 篇)
   ...
```

---

## 步驟五：模擬匯入（Dry Run）

```bash
# 進入 Laravel 容器
docker compose exec app bash

# 模擬執行（完全不寫入資料庫）
php artisan import:static-wordpress \
  /path/to/example.com \
  --dry-run \
  --base-url=https://example.com
```

---

## 步驟六：正式匯入

```bash
php artisan import:static-wordpress \
  /path/to/example.com \
  --author-id=1 \
  --base-url=https://example.com
```

匯入完成後會顯示摘要：

```
 ✅ 成功匯入文章  │ 134
 ⏭  略過        │ 113
 🖼  複製圖片    │ 487
 ❌ 錯誤         │ 2
```

---

## 常見問題排除

### 文章偵測不到

wget 的 HTML 可能使用了自訂主題，WordPress 預設選擇器對不上。
編輯 `ImportStaticWordPress.php` 的 `isPostPage()` 方法，加入你網站使用的 class：

```php
private function isPostPage(Crawler $crawler): bool
{
    $selectors = [
        'article.post',
        '.entry-content',
        '.your-custom-post-class',   // ← 加入你的 class
        // ...
    ];
```

**如何找到正確的 class？**
用瀏覽器開啟 wget 下來的任一篇文章 HTML，按 F12 → 右鍵文章本文 → 「檢查」，看 `<article>` 或最外層 `<div>` 的 class 名稱。

---

### 圖片路徑錯誤

如果圖片是以完整 URL 嵌入（`src="https://example.com/wp-content/..."`)，
需要指定 `--base-url`：

```bash
php artisan import:static-wordpress /path/to/wget \
  --base-url=https://example.com
```

---

### 日期解析失敗

如果你的主題使用了特殊的日期格式，在 `extractDate()` 中加入對應 selector：

```php
$selectors = [
    'time[datetime]'   => 'datetime',
    '.your-date-class' => null,    // ← 加入
];
```

---

## 匯入後的後續工作

1. **登入管理後台** `http://localhost/admin`，快速瀏覽匯入的文章，確認格式正確
2. **封面圖片**：自動匯入的可能不完整，可在後台手動補上
3. **SEO 設定**：確認每篇文章的 meta description 是否正確抓到
4. **舊網址重定向**：在 Nginx 設定 301，確保搜尋引擎排名不流失

```nginx
# 將舊 WordPress URL 導向新系統
location ~ ^/(\d{4})/(\d{2})/(\d{2})/(.+?)/?$ {
    return 301 /blog/$4;
}
```
