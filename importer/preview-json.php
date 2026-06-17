<?php
$data = json_decode(file_get_contents('/var/www/importer/tsanyen.json'), true);
echo "meta: " . json_encode($data['meta'], JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;
foreach ($data['posts'] as $p) {
    echo "--- " . $p['title'] . PHP_EOL;
    echo "  content: " . mb_strlen($p['content']) . " chars" . PHP_EOL;
    echo "  excerpt: " . mb_substr($p['excerpt'], 0, 80) . PHP_EOL;
    echo "  image: " . ($p['featured_image'] ?: '(無)') . PHP_EOL;
    echo PHP_EOL;
}
