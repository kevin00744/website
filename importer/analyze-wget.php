#!/usr/bin/env php
<?php

/**
 * 遷移前分析腳本 — 在匯入前先掃描 wget 目錄，產生報告
 *
 * 使用方式（不需要 Laravel 環境）：
 *   php analyze-wget.php /path/to/wget-folder
 *
 * 輸出：
 *   - 找到多少 HTML 檔案
 *   - 哪些看起來是文章
 *   - 文章的日期範圍
 *   - 圖片數量與大小
 *   - 偵測到的分類 / 標籤
 *   - 目錄結構預覽
 */

if ($argc < 2) {
    echo "使用方式：php analyze-wget.php /path/to/wget-folder\n";
    exit(1);
}

$basePath = rtrim($argv[1], '/');
if (!is_dir($basePath)) {
    echo "❌ 找不到目錄：$basePath\n";
    exit(1);
}

echo "\n📂 分析目錄：$basePath\n";
echo str_repeat('─', 60) . "\n\n";

// ── 統計 ──────────────────────────────────────────
$stats = [
    'html'       => [],
    'posts'      => [],
    'images'     => [],
    'categories' => [],
    'tags'       => [],
    'dates'      => [],
    'totalSize'  => 0,
];

// 掃描所有檔案
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath));
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $ext  = strtolower($file->getExtension());
    $size = $file->getSize();
    $stats['totalSize'] += $size;

    if (in_array($ext, ['html', 'htm'])) {
        $stats['html'][] = $file->getRealPath();
    } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
        $stats['images'][] = ['path' => $file->getRealPath(), 'size' => $size];
    }
}

echo "📄 HTML 檔案：" . count($stats['html']) . " 個\n";
echo "🖼  圖片檔案：" . count($stats['images']) . " 個\n";
echo "💾 總大小：  " . formatBytes($stats['totalSize']) . "\n\n";

// ── 分析 HTML ─────────────────────────────────────
echo "🔍 分析 HTML 內容...\n";

foreach ($stats['html'] as $filePath) {
    $html = @file_get_contents($filePath);
    if (!$html) continue;

    $isPost = isPostPage($html);

    if ($isPost) {
        $title  = extractTitle($html);
        $date   = extractDate($html);
        $cats   = extractTerms($html, 'category');
        $tags   = extractTerms($html, 'tag');
        $slug   = extractSlug($filePath, $basePath);

        $stats['posts'][] = [
            'file'  => str_replace($basePath . '/', '', $filePath),
            'title' => $title,
            'date'  => $date,
            'slug'  => $slug,
            'cats'  => $cats,
            'tags'  => $tags,
        ];

        foreach ($cats as $c) $stats['categories'][$c] = ($stats['categories'][$c] ?? 0) + 1;
        foreach ($tags as $t) $stats['tags'][$t]        = ($stats['tags'][$t] ?? 0) + 1;
        if ($date) $stats['dates'][] = $date;
    }
}

// ── 輸出報告 ──────────────────────────────────────
$postCount = count($stats['posts']);
echo "\n✅ 偵測到文章頁面：{$postCount} 篇\n";
echo "⏭  非文章頁面：" . (count($stats['html']) - $postCount) . " 個\n\n";

if ($stats['dates']) {
    sort($stats['dates']);
    echo "📅 文章日期範圍：" . $stats['dates'][0] . " ～ " . end($stats['dates']) . "\n";
}

if ($stats['categories']) {
    arsort($stats['categories']);
    echo "\n📁 偵測到的分類（前 10 個）：\n";
    $i = 0;
    foreach ($stats['categories'] as $cat => $count) {
        echo "   {$cat} ({$count} 篇)\n";
        if (++$i >= 10) break;
    }
}

if ($stats['tags']) {
    arsort($stats['tags']);
    echo "\n🏷  偵測到的標籤（前 10 個）：\n";
    $i = 0;
    foreach ($stats['tags'] as $tag => $count) {
        echo "   {$tag} ({$count} 篇)\n";
        if (++$i >= 10) break;
    }
}

// ── 目錄結構預覽 ──────────────────────────────────
echo "\n📐 目錄結構（前兩層）：\n";
printDirTree($basePath, $basePath, 0, 2);

// ── 文章列表（前 10 筆） ───────────────────────────
if ($stats['posts']) {
    echo "\n📝 前 10 篇文章預覽：\n";
    $count = 0;
    foreach ($stats['posts'] as $p) {
        echo "   [{$p['date']}] {$p['title']}\n";
        echo "   slug: {$p['slug']}  cats: " . implode(', ', $p['cats']) . "\n\n";
        if (++$count >= 10) break;
    }
}

// ── 匯入指令提示 ──────────────────────────────────
echo str_repeat('─', 60) . "\n";
echo "▶  準備就緒後，執行以下指令匯入：\n\n";
echo "   # 先模擬執行\n";
echo "   php artisan import:static-wordpress \\\n";
echo "     {$basePath} \\\n";
echo "     --dry-run\n\n";
echo "   # 確認無誤後正式匯入\n";
echo "   php artisan import:static-wordpress \\\n";
echo "     {$basePath} \\\n";
echo "     --author-id=1\n\n";

// ════════════════════════════════════════════════
// Helper functions
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
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $html)) return true;
    }
    return false;
}

function extractTitle(string $html): string
{
    // Try h1.entry-title first
    if (preg_match('/<h1[^>]*class=["\'][^"\']*entry-title[^"\']*["\'][^>]*>(.*?)<\/h1>/si', $html, $m)) {
        return trim(strip_tags($m[1]));
    }
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
        return trim(strip_tags($m[1]));
    }
    if (preg_match('/<title>(.*?)<\/title>/si', $html, $m)) {
        return trim(preg_replace('/\s*[|\-–—]\s*.+$/', '', strip_tags($m[1])));
    }
    return '(無標題)';
}

function extractDate(string $html): string
{
    if (preg_match('/<time[^>]+datetime=["\']([^"\']+)["\']/', $html, $m)) {
        $ts = strtotime($m[1]);
        return $ts ? date('Y-m-d', $ts) : '';
    }
    if (preg_match('/published_time["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
        $ts = strtotime($m[1]);
        return $ts ? date('Y-m-d', $ts) : '';
    }
    return '';
}

function extractTerms(string $html, string $type): array
{
    $terms = [];
    $pattern = $type === 'category'
        ? '/rel=["\']category tag["\'][^>]*>(.*?)<\/a>/si'
        : '/rel=["\']tag["\'][^>]*>(.*?)<\/a>/si';

    preg_match_all($pattern, $html, $matches);
    foreach ($matches[1] as $m) {
        $term = trim(strip_tags($m));
        if ($term) $terms[] = $term;
    }
    return array_unique($terms);
}

function extractSlug(string $filePath, string $basePath): string
{
    $relative = str_replace($basePath . '/', '', $filePath);
    $parts    = explode('/', $relative);
    if (end($parts) === 'index.html') {
        array_pop($parts);
        return end($parts) ?: 'post';
    }
    return pathinfo(end($parts), PATHINFO_FILENAME) ?: 'post';
}

function printDirTree(string $dir, string $base, int $depth, int $maxDepth): void
{
    if ($depth > $maxDepth) return;
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        $rel  = str_replace($base . '/', '', $path);
        echo str_repeat('   ', $depth) . ($depth === 0 ? '' : '├─ ') . $item;
        if (is_dir($path)) {
            echo "/\n";
            printDirTree($path, $base, $depth + 1, $maxDepth);
        } else {
            $size = formatBytes(filesize($path));
            echo "  ({$size})\n";
        }
    }
}

function formatBytes(int $bytes): string
{
    if ($bytes < 1024)    return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}
