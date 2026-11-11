<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = App\Models\SiteSetting::first();
echo "SiteSetting:\n";
print_r($settings ? $settings->toArray() : null);

