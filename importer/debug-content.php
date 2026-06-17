<?php
require '/var/www/vendor/autoload.php';
use Symfony\Component\DomCrawler\Crawler;

$file = '/var/www/tsanyen.com/face.html';
$html = file_get_contents($file);
$crawler = new Crawler($html);

// Simulate extractContent from ImportStaticWordPress
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
        $count = $node->count();
        echo "$sel → $count matches\n";
        if ($count > 0) {
            echo "  content length: " . strlen($node->first()->html()) . "\n";
            break;
        }
    } catch (Exception $e) {
        echo "$sel → ERROR\n";
    }
}

echo "\nmain content length: ";
try {
    $main = $crawler->filter('main');
    echo $main->count() > 0 ? strlen($main->first()->html()) : 0;
} catch (Exception $e) { echo "0"; }
echo "\n";
