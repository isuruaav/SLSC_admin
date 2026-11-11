<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$layouts = App\Models\ThemeLayout::all(['id', 'theme_slug', 'region', 'locale', 'status']);
echo json_encode($layouts->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
