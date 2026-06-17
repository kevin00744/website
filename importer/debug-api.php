<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $post = App\Models\Post::published()
        ->where('slug', 'face')
        ->with(['author:id,name,avatar,bio', 'category', 'tags', 'featuredImage'])
        ->firstOrFail();
    echo "OK: " . $post->title . "\n";
    $post->incrementViews();
    echo "Views incremented OK\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
