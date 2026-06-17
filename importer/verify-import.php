<?php
require '/var/www/vendor/autoload.php';

$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$posts = App\Models\Post::select('id','title','slug','status','content','published_at')->get();

echo "=== 資料庫中的文章 (共 " . $posts->count() . " 篇) ===\n\n";
foreach ($posts as $p) {
    echo "ID: {$p->id}\n";
    echo "標題: {$p->title}\n";
    echo "Slug: {$p->slug}\n";
    echo "狀態: {$p->status}\n";
    echo "發布日期: " . ($p->published_at ?? '(無)') . "\n";
    echo "內容長度: " . mb_strlen($p->content) . " 字元\n";
    echo "\n";
}

// Check storage for images
echo "=== 已複製的圖片 ===\n";
$storagePath = '/var/www/storage/app/public/uploads';
if (is_dir($storagePath)) {
    $count = count(glob($storagePath . '/**/*.*', GLOB_BRACE));
    echo "storage/app/public/uploads/ 中的圖片：$count 個\n";
} else {
    echo "(storage 目錄不存在)\n";
}
