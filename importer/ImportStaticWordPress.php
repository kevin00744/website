<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

/**
 * 從 wget 爬下的 WordPress 靜態檔案匯入到 Laravel CMS
 *
 * 安裝依賴：
 *   composer require symfony/dom-crawler symfony/css-selector
 *
 * 使用方式：
 *   php artisan import:static-wordpress /path/to/wget-folder
 *
 * 選項：
 *   --dry-run          預覽，不寫入資料庫
 *   --skip-images      不複製圖片
 *   --author-id=1      指定匯入文章的作者 ID
 *   --base-url=        原網站網址 (例: https://example.com)，用於修正圖片路徑
 */
class ImportStaticWordPress extends Command
{
    protected $signature = 'import:static-wordpress
        {path : wget 靜態資料夾路徑}
        {--dry-run      : 模擬執行，不寫入任何資料}
        {--skip-images  : 略過圖片複製}
        {--author-id=1  : 匯入文章的作者 User ID}
        {--base-url=    : 原始網站網址 (https://example.com)}';

    protected $description = '從 wget 靜態 HTML 匯入 WordPress 文章到 Laravel CMS';

    // 統計
    private int $imported   = 0;
    private int $skipped    = 0;
    private int $imagesCopied = 0;
    private array $errors   = [];

    // 快取
    private array $categoryCache = [];
    private array $tagCache      = [];

    private bool   $dryRun;
    private string $basePath;
    private string $baseUrl;
    private int    $authorId;

    public function handle(): int
    {
        $this->basePath = rtrim($this->argument('path'), '/');
        $this->dryRun   = $this->option('dry-run');
        $this->authorId = (int) $this->option('author-id');
        $this->baseUrl  = rtrim($this->option('base-url') ?? '', '/');

        if (!is_dir($this->basePath)) {
            $this->error("找不到資料夾：{$this->basePath}");
            return Command::FAILURE;
        }

        $this->info("📂 掃描目錄：{$this->basePath}");
        if ($this->dryRun) {
            $this->warn('🔍 DRY RUN 模式 — 不會寫入任何資料');
        }

        // 找出所有 HTML 檔案
        $htmlFiles = $this->findHtmlFiles();
        $this->info("🔎 找到 " . count($htmlFiles) . " 個 HTML 檔案，開始分析...\n");

        $bar = $this->output->createProgressBar(count($htmlFiles));
        $bar->start();

        foreach ($htmlFiles as $file) {
            $this->processFile($file);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // 結果摘要
        $this->table(
            ['項目', '數量'],
            [
                ['✅ 成功匯入文章', $this->imported],
                ['⏭  略過 (非文章頁面)', $this->skipped],
                ['🖼  複製圖片', $this->imagesCopied],
                ['❌ 錯誤', count($this->errors)],
            ]
        );

        if ($this->errors) {
            $this->newLine();
            $this->warn('發生錯誤的檔案：');
            foreach ($this->errors as $err) {
                $this->line("  {$err}");
            }
        }

        return Command::SUCCESS;
    }

    // ────────────────────────────────────────────
    // 檔案掃描
    // ────────────────────────────────────────────

    private function findHtmlFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;

            $ext = strtolower($file->getExtension());
            // index.html 是目錄頁，也掃描；其他 .html 一律納入
            if (in_array($ext, ['html', 'htm'])) {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    // ────────────────────────────────────────────
    // 單一檔案處理
    // ────────────────────────────────────────────

    private function processFile(string $filePath): void
    {
        try {
            $html    = file_get_contents($filePath);
            $crawler = new Crawler($html);

            // 判斷是否為文章頁（WordPress 文章通常有 article 標籤或 .post class）
            if (!$this->isPostPage($crawler)) {
                $this->skipped++;
                return;
            }

            $data = $this->extractPostData($crawler, $filePath);

            if (empty($data['title']) || empty($data['content'])) {
                $this->skipped++;
                return;
            }

            // 複製文章內的圖片
            if (!$this->option('skip-images')) {
                $data['content'] = $this->processImages($data['content'], $filePath);
            }

            if (!$this->dryRun) {
                $this->savePost($data);
            } else {
                $this->line("\n  [DRY RUN] 文章: {$data['title']}");
            }

            $this->imported++;

        } catch (\Throwable $e) {
            $this->errors[] = basename($filePath) . ': ' . $e->getMessage();
        }
    }

    // ────────────────────────────────────────────
    // 判斷是否為文章頁
    // ────────────────────────────────────────────

    private function isPostPage(Crawler $crawler): bool
    {
        // WordPress 文章頁的常見特徵
        $selectors = [
            'article.post',
            'article.type-post',
            '.post-content',
            '.entry-content',
            '.post-body',
            '#post-',           // id 前綴
            '.single-post',
            '[class*="post-"] article',
        ];

        foreach ($selectors as $selector) {
            try {
                if ($crawler->filter($selector)->count() > 0) {
                    return true;
                }
            } catch (\Exception $e) {}
        }

        // 也檢查 <body> class 是否含有 "single" (WordPress single post)
        try {
            $bodyClass = $crawler->filter('body')->attr('class') ?? '';
            if (str_contains($bodyClass, 'single') || str_contains($bodyClass, 'post-')) {
                return true;
            }
        } catch (\Exception $e) {}

        return false;
    }

    // ────────────────────────────────────────────
    // 提取文章資料
    // ────────────────────────────────────────────

    private function extractPostData(Crawler $crawler, string $filePath): array
    {
        return [
            'title'        => $this->extractTitle($crawler),
            'content'      => $this->extractContent($crawler),
            'excerpt'      => $this->extractExcerpt($crawler),
            'slug'         => $this->extractSlug($filePath),
            'published_at' => $this->extractDate($crawler),
            'categories'   => $this->extractCategories($crawler),
            'tags'         => $this->extractTags($crawler),
            'featured_image' => $this->extractFeaturedImage($crawler, $filePath),
            'seo_title'    => $this->extractSeoTitle($crawler),
            'seo_description' => $this->extractSeoDescription($crawler),
        ];
    }

    private function extractTitle(Crawler $crawler): string
    {
        // 嘗試多個選擇器，依優先順序
        $selectors = [
            'article h1.entry-title',
            'article h1.post-title',
            'h1.entry-title',
            'h1.post-title',
            'h1.title',
            '.post h1',
            'article h1',
            'h1',
        ];

        foreach ($selectors as $sel) {
            try {
                $node = $crawler->filter($sel);
                if ($node->count() > 0) {
                    return trim($node->first()->text());
                }
            } catch (\Exception $e) {}
        }

        // fallback: <title> tag 去掉網站名稱
        try {
            $title = $crawler->filter('title')->text();
            // 去掉 "| 網站名稱" 後綴
            return trim(preg_replace('/\s*[|\-–—]\s*.+$/', '', $title));
        } catch (\Exception $e) {}

        return '';
    }

    private function extractContent(Crawler $crawler): string
    {
        $selectors = [
            '.entry-content',
            '.post-content',
            '.post-body',
            'article .content',
            '.article-content',
            'article',
        ];

        foreach ($selectors as $sel) {
            try {
                $node = $crawler->filter($sel);
                if ($node->count() > 0) {
                    $html = $node->first()->html();
                    return $this->cleanContent($html);
                }
            } catch (\Exception $e) {}
        }

        return '';
    }

    private function cleanContent(string $html): string
    {
        // 移除常見的 WordPress UI 元素
        $patterns = [
            '/<div[^>]*class="[^"]*share[^"]*"[^>]*>.*?<\/div>/si',
            '/<div[^>]*class="[^"]*social[^"]*"[^>]*>.*?<\/div>/si',
            '/<div[^>]*class="[^"]*related[^"]*"[^>]*>.*?<\/div>/si',
            '/<div[^>]*class="[^"]*comments?[^"]*"[^>]*>.*?<\/div>/si',
            '/<div[^>]*class="[^"]*navigation[^"]*"[^>]*>.*?<\/div>/si',
            '/<div[^>]*class="[^"]*wp-caption[^"]*"[^>]*>.*?<\/div>/si',
            '/<nav[^>]*>.*?<\/nav>/si',
            '/<!--.*?-->/s',
        ];

        foreach ($patterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        return trim($html);
    }

    private function extractExcerpt(Crawler $crawler): string
    {
        // 嘗試 meta description 或第一段文字
        try {
            $meta = $crawler->filter('meta[name="description"]');
            if ($meta->count() > 0) {
                $desc = $meta->attr('content');
                if ($desc) return Str::limit(strip_tags($desc), 300);
            }
        } catch (\Exception $e) {}

        // 第一段文字
        try {
            $selectors = ['.entry-content p', '.post-content p', 'article p'];
            foreach ($selectors as $sel) {
                $p = $crawler->filter($sel);
                if ($p->count() > 0) {
                    $text = strip_tags($p->first()->text());
                    if (strlen($text) > 20) {
                        return Str::limit($text, 300);
                    }
                }
            }
        } catch (\Exception $e) {}

        return '';
    }

    private function extractSlug(string $filePath): string
    {
        // 從路徑推導 slug
        // 例: /var/wget/example.com/2023/05/my-post/index.html → my-post
        $relative = str_replace($this->basePath . '/', '', $filePath);
        $parts    = explode('/', $relative);

        // 若最後一個是 index.html，取倒數第二層
        if (end($parts) === 'index.html' || end($parts) === 'index.htm') {
            array_pop($parts);
            $slug = end($parts);
        } else {
            $slug = pathinfo(end($parts), PATHINFO_FILENAME);
        }

        return Str::slug($slug) ?: Str::uuid();
    }

    private function extractDate(Crawler $crawler): ?string
    {
        // 嘗試多種日期標記
        $selectors = [
            'time[datetime]'     => 'datetime',
            'meta[property="article:published_time"]' => 'content',
            '.entry-date'        => null,
            '.post-date'         => null,
            '.published'         => null,
            'time'               => null,
        ];

        foreach ($selectors as $selector => $attr) {
            try {
                $node = $crawler->filter($selector);
                if ($node->count() === 0) continue;

                $value = $attr ? $node->first()->attr($attr) : $node->first()->text();
                if ($value) {
                    $ts = strtotime($value);
                    if ($ts) return date('Y-m-d H:i:s', $ts);
                }
            } catch (\Exception $e) {}
        }

        return now()->toDateTimeString();
    }

    private function extractCategories(Crawler $crawler): array
    {
        $categories = [];
        $selectors  = [
            '.cat-links a',
            '.category-links a',
            'a[rel="category tag"]',
            '.entry-categories a',
            '.post-categories a',
        ];

        foreach ($selectors as $sel) {
            try {
                $crawler->filter($sel)->each(function (Crawler $node) use (&$categories) {
                    $name = trim($node->text());
                    if ($name) $categories[] = $name;
                });
                if ($categories) break;
            } catch (\Exception $e) {}
        }

        return array_unique($categories);
    }

    private function extractTags(Crawler $crawler): array
    {
        $tags      = [];
        $selectors = [
            '.tags-links a',
            '.tag-links a',
            'a[rel="tag"]',
            '.entry-tags a',
            '.post-tags a',
        ];

        foreach ($selectors as $sel) {
            try {
                $crawler->filter($sel)->each(function (Crawler $node) use (&$tags) {
                    $name = trim($node->text());
                    if ($name) $tags[] = $name;
                });
                if ($tags) break;
            } catch (\Exception $e) {}
        }

        return array_unique($tags);
    }

    private function extractFeaturedImage(Crawler $crawler, string $filePath): ?string
    {
        $selectors = [
            '.post-thumbnail img',
            '.featured-image img',
            'article .wp-post-image',
            'meta[property="og:image"]' => 'content',
        ];

        foreach ($selectors as $sel => $attr) {
            try {
                if (is_string($attr)) {
                    $node = $crawler->filter($sel);
                    if ($node->count() > 0) return $node->attr($attr);
                } else {
                    $node = $crawler->filter($sel);
                    if ($node->count() > 0) return $node->first()->attr('src');
                }
            } catch (\Exception $e) {}
        }

        return null;
    }

    private function extractSeoTitle(Crawler $crawler): string
    {
        try {
            $node = $crawler->filter('meta[property="og:title"]');
            if ($node->count() > 0) return $node->attr('content') ?? '';
        } catch (\Exception $e) {}
        return '';
    }

    private function extractSeoDescription(Crawler $crawler): string
    {
        try {
            $node = $crawler->filter('meta[name="description"], meta[property="og:description"]');
            if ($node->count() > 0) return $node->first()->attr('content') ?? '';
        } catch (\Exception $e) {}
        return '';
    }

    // ────────────────────────────────────────────
    // 圖片處理
    // ────────────────────────────────────────────

    private function processImages(string $content, string $sourceFile): string
    {
        $sourceDir = dirname($sourceFile);

        return preg_replace_callback(
            '/<img([^>]+)src=["\']([^"\']+)["\']([^>]*)>/i',
            function ($matches) use ($sourceDir) {
                $src = $matches[2];

                // 跳過外部圖片 (http/https 且非本站)
                if (
                    (str_starts_with($src, 'http') || str_starts_with($src, '//')) &&
                    $this->baseUrl &&
                    !str_starts_with($src, $this->baseUrl)
                ) {
                    return $matches[0];
                }

                $localPath = $this->resolveImagePath($src, $sourceDir);
                if (!$localPath || !file_exists($localPath)) {
                    return $matches[0]; // 找不到就保留原標籤
                }

                $newUrl = $this->copyImageToStorage($localPath);
                if (!$newUrl) return $matches[0];

                return "<img{$matches[1]}src=\"{$newUrl}\"{$matches[3]}>";
            },
            $content
        );
    }

    private function resolveImagePath(string $src, string $sourceDir): ?string
    {
        // 移除網址前綴，只取路徑部分
        if (str_starts_with($src, $this->baseUrl)) {
            $src = substr($src, strlen($this->baseUrl));
        }
        if (str_starts_with($src, 'http')) {
            // 無法解析外部完整 URL 對應到本地路徑
            // 嘗試從 wget 下載的目錄結構找
            $parsed = parse_url($src);
            $src = $parsed['path'] ?? $src;
        }

        // 絕對路徑：從 basePath 找
        if (str_starts_with($src, '/')) {
            return $this->basePath . $src;
        }

        // 相對路徑：從當前 HTML 所在目錄解析
        return realpath($sourceDir . '/' . $src) ?: null;
    }

    private function copyImageToStorage(string $localPath): ?string
    {
        if ($this->dryRun) {
            $this->imagesCopied++;
            return '/storage/uploads/dry-run/' . basename($localPath);
        }

        try {
            $ext       = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
            $destName  = Str::uuid() . '.' . $ext;
            $destPath  = 'uploads/' . date('Y/m') . '/' . $destName;

            Storage::disk('public')->put($destPath, file_get_contents($localPath));
            $this->imagesCopied++;

            return Storage::disk('public')->url($destPath);
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ────────────────────────────────────────────
    // 儲存到資料庫
    // ────────────────────────────────────────────

    private function savePost(array $data): void
    {
        // 確保 slug 唯一
        $slug = $this->ensureUniqueSlug($data['slug']);

        // 取得或建立分類
        $categoryId = null;
        if (!empty($data['categories'])) {
            $catName    = $data['categories'][0]; // 主分類用第一個
            $categoryId = $this->getOrCreateCategory($catName);
        }

        // 取得或建立標籤
        $tagIds = [];
        foreach ($data['tags'] as $tagName) {
            $tagIds[] = $this->getOrCreateTag($tagName);
        }

        $post = Post::create([
            'user_id'         => $this->authorId,
            'category_id'     => $categoryId,
            'title'           => $data['title'],
            'slug'            => $slug,
            'excerpt'         => $data['excerpt'],
            'content'         => $data['content'],
            'status'          => 'published',
            'type'            => 'post',
            'seo_title'       => $data['seo_title'],
            'seo_description' => $data['seo_description'],
            'published_at'    => $data['published_at'],
        ]);

        if ($tagIds) {
            $post->tags()->sync($tagIds);
        }
    }

    private function getOrCreateCategory(string $name): int
    {
        $name = trim($name);
        if (isset($this->categoryCache[$name])) {
            return $this->categoryCache[$name];
        }

        $cat = Category::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        );

        return $this->categoryCache[$name] = $cat->id;
    }

    private function getOrCreateTag(string $name): int
    {
        $name = trim($name);
        if (isset($this->tagCache[$name])) {
            return $this->tagCache[$name];
        }

        $tag = Tag::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        );

        return $this->tagCache[$name] = $tag->id;
    }

    private function ensureUniqueSlug(string $slug): string
    {
        $base = $slug ?: 'post';
        $candidate = $base;
        $i = 1;
        while (Post::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $i++;
        }
        return $candidate;
    }
}
