#!/usr/bin/env php
<?php

/**
 * 從 wget 靜態 HTML 萃取文章，輸出為 JSON
 *
 * 使用方式：
 *   php extract-to-json.php /path/to/tsanyen.com [output.json]
 */

if ($argc < 2) {
    echo "使用方式：php extract-to-json.php /path/to/wget-folder [output.json]\n";
    exit(1);
}

$basePath   = rtrim($argv[1], '/\\');
$outputFile = $argv[2] ?? dirname($basePath) . '/' . basename($basePath) . '.json';

if (!is_dir($basePath)) {
    echo "❌ 找不到目錄：$basePath\n";
    exit(1);
}

echo "\n📂 掃描目錄：$basePath\n";

// ── 掃描所有 HTML ──────────────────────────────────────
$htmlFiles = [];
$iterator  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath));
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $ext = strtolower($file->getExtension());
    if (in_array($ext, ['html', 'htm'])) {
        $htmlFiles[] = $file->getRealPath();
    }
}

echo "🔎 找到 " . count($htmlFiles) . " 個 HTML 檔案，開始分析...\n\n";

$posts    = [];
$skipped  = 0;

foreach ($htmlFiles as $filePath) {
    $html = @file_get_contents($filePath);
    if (!$html) { $skipped++; continue; }

    if (!isPostPage($html)) { $skipped++; continue; }

    $title   = extractTitle($html);
    $content = extractContent($html);

    if (!$title && !$content) { $skipped++; continue; }

    $posts[] = [
        'title'           => $title,
        'slug'            => extractSlug($filePath, $basePath),
        'excerpt'         => extractExcerpt($html),
        'content'         => $content,
        'published_at'    => extractDate($html),
        'categories'      => extractTerms($html, 'category'),
        'tags'            => extractTerms($html, 'tag'),
        'featured_image'  => extractFeaturedImage($html),
        'seo_title'       => extractMetaAttr($html, 'og:title'),
        'seo_description' => extractMetaAttr($html, 'description') ?: extractMetaAttr($html, 'og:description'),
        'source_file'     => str_replace($basePath . DIRECTORY_SEPARATOR, '', $filePath),
    ];
}

// ── 輸出 JSON ─────────────────────────────────────────
$output = [
    'meta' => [
        'source'       => $basePath,
        'exported_at'  => date('Y-m-d H:i:s'),
        'total_posts'  => count($posts),
        'skipped'      => $skipped,
    ],
    'posts' => $posts,
];

file_put_contents($outputFile, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ 成功萃取 " . count($posts) . " 篇文章\n";
echo "⏭  略過 $skipped 個非文章頁面\n";
echo "💾 輸出檔案：$outputFile\n\n";

// 預覽
foreach ($posts as $i => $p) {
    $date = $p['published_at'] ?: '(無日期)';
    echo "  [{$date}] {$p['title']}\n";
    echo "  slug: {$p['slug']}\n";
    if ($p['categories']) echo "  分類: " . implode(', ', $p['categories']) . "\n";
    if ($p['tags'])       echo "  標籤: " . implode(', ', $p['tags']) . "\n";
    echo "\n";
}

// ════════════════════════════════════════════════
// Helpers
// ════════════════════════════════════════════════

function isPostPage(string $html): bool
{
    $patterns = [
        '/class=["\'][^"\']*\bpost\b[^"\']*["\']/',
        '/class=["\'][^"\']*\bentry-content\b[^"\']*["\']/',
        '/class=["\'][^"\']*\bpost-content\b[^"\']*["\']/',
        '/<article[^>]+class=["\'][^"\']*post/',
        '/rel=["\']category tag["\']/',
        '/<body[^>]+class=["\'][^"\']*\bsingle\b/',
        '/<body[^>]+class=["\'][^"\']*\bpage\b/',   // WordPress pages
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $html)) return true;
    }
    return false;
}

function extractTitle(string $html): string
{
    if (preg_match('/<h1[^>]*class=["\'][^"\']*entry-title[^"\']*["\'][^>]*>(.*?)<\/h1>/si', $html, $m))
        return trim(strip_tags($m[1]));
    if (preg_match('/<h1[^>]*class=["\'][^"\']*page-title[^"\']*["\'][^>]*>(.*?)<\/h1>/si', $html, $m))
        return trim(strip_tags($m[1]));
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m))
        return trim(strip_tags($m[1]));
    if (preg_match('/<title>(.*?)<\/title>/si', $html, $m))
        return trim(preg_replace('/\s*[|\-–—]\s*.+$/', '', strip_tags($m[1])));
    return '';
}

function extractContent(string $html): string
{
    // 嘗試多個常見內容區塊選擇器（用正則）
    $patterns = [
        '/<div[^>]+class=["\'][^"\']*entry-content[^"\']*["\'][^>]*>(.*?)<\/div>\s*(?:<\/div>|$)/si',
        '/<div[^>]+class=["\'][^"\']*post-content[^"\']*["\'][^>]*>(.*?)<\/div>/si',
        '/<div[^>]+class=["\'][^"\']*elementor-widget-container[^"\']*["\'][^>]*>(.*?)<\/div>/si',
        '/<article[^>]*>(.*?)<\/article>/si',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $m)) {
            return cleanContent($m[1]);
        }
    }

    // fallback: <main> 區塊
    if (preg_match('/<main[^>]*>(.*?)<\/main>/si', $html, $m)) {
        return cleanContent($m[1]);
    }

    return '';
}

function cleanContent(string $html): string
{
    // 移除 script / style
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html);
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $html);
    // 移除 HTML 注釋
    $html = preg_replace('/<!--.*?-->/s', '', $html);
    // 移除導覽、社群分享等常見雜訊
    $noisePatterns = [
        '/<nav\b[^>]*>.*?<\/nav>/si',
        '/<div[^>]+class=["\'][^"\']*(?:share|social|related|comments?|navigation|wp-caption)[^"\']*["\'][^>]*>.*?<\/div>/si',
    ];
    foreach ($noisePatterns as $p) {
        $html = preg_replace($p, '', $html);
    }
    return trim($html);
}

function extractExcerpt(string $html): string
{
    // meta description 優先
    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $m))
        return mb_substr(strip_tags($m[1]), 0, 300);

    // Elementor: 從 text-editor widget 找第一段有意義的文字
    if (preg_match_all('/<div[^>]+class=["\'][^"\']*elementor-widget-text-editor[^"\']*["\'][^>]*>(.*?)<\/div>\s*<\/div>/si', $html, $blocks)) {
        foreach ($blocks[1] as $block) {
            if (preg_match('/<p[^>]*>(.*?)<\/p>/si', $block, $m)) {
                $text = trim(strip_tags($m[1]));
                if (mb_strlen($text) > 30) return mb_substr($text, 0, 300);
            }
        }
    }

    // 一般 <p>，跳過短於 40 字的（通常是導覽文字）
    preg_match_all('/<p[^>]*>(.*?)<\/p>/si', $html, $matches);
    foreach ($matches[1] as $m) {
        $text = trim(strip_tags($m));
        if (mb_strlen($text) > 40) return mb_substr($text, 0, 300);
    }

    return '';
}

function extractDate(string $html): string
{
    if (preg_match('/<time[^>]+datetime=["\']([^"\']+)["\']/', $html, $m)) {
        $ts = strtotime($m[1]);
        return $ts ? date('Y-m-d H:i:s', $ts) : '';
    }
    if (preg_match('/published_time["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
        $ts = strtotime($m[1]);
        return $ts ? date('Y-m-d H:i:s', $ts) : '';
    }
    return '';
}

function extractTerms(string $html, string $type): array
{
    $terms   = [];
    $pattern = $type === 'category'
        ? '/rel=["\']category tag["\'][^>]*>(.*?)<\/a>/si'
        : '/rel=["\']tag["\'][^>]*>(.*?)<\/a>/si';
    preg_match_all($pattern, $html, $matches);
    foreach ($matches[1] as $m) {
        $term = trim(strip_tags($m));
        if ($term) $terms[] = $term;
    }
    return array_values(array_unique($terms));
}

function extractFeaturedImage(string $html): string
{
    // og:image
    if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $m))
        return $m[1];
    if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\'][^>]*>/i', $html, $m))
        return $m[1];
    // .wp-post-image
    if (preg_match('/<img[^>]+class=["\'][^"\']*wp-post-image[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $m))
        return $m[1];
    // Elementor: 第一張 wp-content/uploads 的圖片（排除 logo）
    if (preg_match_all('/<img[^>]+src=["\']([^"\']*wp-content\/uploads[^"\']*)["\'][^>]*>/i', $html, $m)) {
        foreach ($m[1] as $src) {
            // 跳過 logo 和 icon（路徑或尺寸特徵）
            if (stripos($src, 'logo') !== false) continue;
            if (preg_match('/[-_]\d+x\d+\.(jpg|png|webp)/i', $src)) {
                // 優先選有具體尺寸的非縮圖（1024 寬以上）
                if (preg_match('/-1024x/i', $src)) return $src;
            }
        }
        // fallback: 第一個 uploads 圖片
        foreach ($m[1] as $src) {
            if (stripos($src, 'logo') !== false) continue;
            return $src;
        }
    }
    return '';
}

function extractMetaAttr(string $html, string $name): string
{
    // property="name" 或 name="name"
    $pattern = '/<meta[^>]+(?:property|name)=["\']' . preg_quote($name, '/') . '["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i';
    if (preg_match($pattern, $html, $m)) return trim($m[1]);
    // 也試試反過來的屬性順序
    $pattern2 = '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']' . preg_quote($name, '/') . '["\'][^>]*>/i';
    if (preg_match($pattern2, $html, $m)) return trim($m[1]);
    return '';
}

function extractSlug(string $filePath, string $basePath): string
{
    $relative = str_replace([$basePath . '/', $basePath . '\\'], '', $filePath);
    $parts    = preg_split('/[\/\\\\]/', $relative);
    if (in_array(end($parts), ['index.html', 'index.htm'])) {
        array_pop($parts);
        return end($parts) ?: 'post';
    }
    return pathinfo(end($parts), PATHINFO_FILENAME) ?: 'post';
}
