<?php
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/login', 'GET');
try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    $content = $response->getContent();
    echo "Content length: " . strlen($content) . "\n";
    if ($response->getStatusCode() >= 400) {
        echo substr($content, 0, 500) . "\n";
    } else {
        echo substr($content, 0, 200) . "\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
