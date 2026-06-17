<?php
require '/var/www/vendor/autoload.php';
use Symfony\Component\DomCrawler\Crawler;

$file = '/var/www/tsanyen.com/face.html';
$html = file_get_contents($file);
$crawler = new Crawler($html);

echo "=== Testing face.html ===\n\n";

// Test each selector
$selectors = [
    '.elementor-widget-text-editor',
    '.elementor-page',
    'article',
    'h1',
    'main',
];
foreach ($selectors as $sel) {
    try {
        $count = $crawler->filter($sel)->count();
        echo "  $sel → $count matches\n";
    } catch (Exception $e) {
        echo "  $sel → ERROR: {$e->getMessage()}\n";
    }
}

// Test body class
try {
    $bodyClass = $crawler->filter('body')->attr('class') ?? '';
    echo "\nbody class: " . substr($bodyClass, 0, 120) . "\n";
    echo "  contains wp-singular: " . (str_contains($bodyClass, 'wp-singular') ? 'YES' : 'NO') . "\n";
    echo "  contains elementor-page: " . (str_contains($bodyClass, 'elementor-page') ? 'YES' : 'NO') . "\n";
    echo "  page-id match: " . (preg_match('/\bpage-id-\d+\b/', $bodyClass) ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "body ERROR: {$e->getMessage()}\n";
}

// Test title extraction
echo "\n=== Title extraction ===\n";
$titleSelectors = ['h1.entry-title', 'h1.page-title', 'h1', '.elementor-heading-title'];
foreach ($titleSelectors as $sel) {
    try {
        $node = $crawler->filter($sel);
        if ($node->count() > 0) {
            echo "  $sel → " . trim($node->first()->text()) . "\n";
        }
    } catch (Exception $e) {}
}
