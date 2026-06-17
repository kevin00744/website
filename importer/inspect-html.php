<?php
// 分析 Elementor 頁面結構，找出正確的內容選擇器
$file = '/var/www/tsanyen.com/face.html';
$html = file_get_contents($file);

// 1. 找 og:image
preg_match_all('/<meta[^>]+(?:property|name)=["\']og:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $m);
echo "=== og:image ===\n";
print_r($m[1]);

// 2. 找所有 <img> src (前10個)
preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $m);
echo "\n=== img src (前5個) ===\n";
foreach (array_slice($m[1], 0, 5) as $src) echo "  $src\n";

// 3. 找 elementor 內容區塊的 class
preg_match_all('/class=["\']([^"\']*elementor-widget-text[^"\']*)["\']/', $html, $m);
echo "\n=== elementor-widget-text classes ===\n";
print_r(array_unique($m[1]));

// 4. 找 body class
preg_match('/<body[^>]+class=["\']([^"\']+)["\']/', $html, $m);
echo "\n=== body class ===\n";
echo $m[1] ?? '(無)';
echo "\n";

// 5. 找第一個 <section> 或 <main>
if (preg_match('/<main[^>]*>(.*?)<\/main>/si', $html, $m)) {
    echo "\n=== <main> 前 300 字元 ===\n";
    echo mb_substr(strip_tags($m[1]), 0, 300);
}
