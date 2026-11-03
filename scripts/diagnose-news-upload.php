<?php

// CLI only: collect upload/runtime facts without changing application data.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = realpath($argv[1] ?? getcwd());
if ($root === false || ! is_file($root.'/artisan') || ! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Usage: php diagnose.php /absolute/path/to/laravel-project\n");
    exit(1);
}

$report = [
    'runtime' => [
        'scope' => 'PHP CLI; the web PHP process may use different ini/OPcache settings',
        'php' => PHP_VERSION,
        'mbstring' => extension_loaded('mbstring'),
        'mb_internal_encoding' => function_exists('mb_internal_encoding') ? mb_internal_encoding() : null,
        'default_charset' => ini_get('default_charset'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'gd' => extension_loaded('gd'),
        'webp' => function_exists('imagewebp'),
        'exif' => extension_loaded('exif'),
        'fileinfo' => extension_loaded('fileinfo'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'exception_ignore_args' => ini_get('zend.exception_ignore_args'),
    ],
];

$paths = [
    'app/Http/Controllers/LivewireUpdateController.php',
    'app/Providers/AppServiceProvider.php',
    'app/Services/LivewireOutputEncoding.php',
    'resources/views/livewire/admin/news/news-edit.blade.php',
    'resources/views/livewire/admin/news/news-create.blade.php',
];
$manifest = is_file(__DIR__.'/PATCH-MANIFEST.json')
    ? json_decode(file_get_contents(__DIR__.'/PATCH-MANIFEST.json'), true)
    : [];
foreach ($paths as $path) {
    $exists = is_file($root.'/'.$path);
    $bytes = $exists ? file_get_contents($root.'/'.$path) : '';
    $expected = $manifest['files'][$path]['sha256'] ?? null;
    $report['application_files'][$path] = [
        'exists' => $exists,
        'valid_utf8' => $exists ? preg_match('//u', $bytes) === 1 : null,
        'matches_this_package' => $exists && $expected !== null ? hash('sha256', $bytes) === $expected : null,
    ];
}

$invalidViews = [];
foreach (glob($root.'/storage/framework/views/*.php') ?: [] as $path) {
    if (preg_match('//u', file_get_contents($path)) !== 1) {
        $invalidViews[] = basename($path);
    }
}
$report['invalid_utf8_compiled_views'] = $invalidViews;

try {
    $loader = require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $report['authoritative_class_map'] = $loader->isClassMapAuthoritative();
    $report['output_guard_autoloadable'] = class_exists(App\Services\LivewireOutputEncoding::class);
    $report['livewire_version'] = Composer\InstalledVersions::getPrettyVersion('livewire/livewire');
    $updateRoute = app('router')->getRoutes()->getByName('livewire.update');
    $report['utf8_update_endpoint_registered'] = $updateRoute?->getActionName() === App\Http\Controllers\LivewireUpdateController::class;
    $connection = config('database.default');
    $report['database_encoding'] = [
        'driver' => config('database.connections.'.$connection.'.driver'),
        'charset' => config('database.connections.'.$connection.'.charset'),
        'collation' => config('database.connections.'.$connection.'.collation'),
    ];
    $report['temporary_upload_disk'] = Livewire\Features\SupportFileUploads\FileUploadConfiguration::disk();
    $report['temporary_upload_rules'] = Livewire\Features\SupportFileUploads\FileUploadConfiguration::rules();
    $diskConfig = config('filesystems.disks.'.$report['temporary_upload_disk'], []);
    if (($diskConfig['driver'] ?? null) === 'local' && is_string($diskConfig['root'] ?? null)) {
        $directory = rtrim($diskConfig['root'], '/\\').'/'.Livewire\Features\SupportFileUploads\FileUploadConfiguration::directory();
        $report['temporary_upload_directory'] = [
            'exists' => is_dir($directory),
            'writable' => is_writable(is_dir($directory) ? $directory : $diskConfig['root']),
        ];
    }

    // Exercise the same image pipeline using a synthetic image, never a user's upload.
    try {
        $probeBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6E8AAAAASUVORK5CYII=');
        if (function_exists('imagecreatetruecolor') && function_exists('imagejpeg')) {
            $probe = imagecreatetruecolor(2, 2);
            $stream = fopen('php://temp', 'w+b');
            imagejpeg($probe, $stream);
            rewind($stream);
            $probeBytes = stream_get_contents($stream);
            fclose($stream);
            imagedestroy($probe);
        }
        $encoded = Illuminate\Support\Facades\Image::fromBytes($probeBytes)
            ->orient()->scale(width: 1, height: 1)->toWebp()->quality(82)->toBytes();
        $report['image_processing_probe'] = [
            'passed' => $encoded !== '',
            'output_mime' => (new finfo(FILEINFO_MIME_TYPE))->buffer($encoded),
        ];
    } catch (Throwable $error) {
        $report['image_processing_probe'] = [
            'passed' => false,
            'exception' => get_class($error),
            'message' => $error->getMessage(),
        ];
    }

    // Exercise the registered response hook without logging the synthetic byte.
    $logger = Illuminate\Support\Facades\Log::getFacadeRoot();
    Illuminate\Support\Facades\Log::swap(new Psr\Log\NullLogger);
    try {
        $payload = [
            'components' => [[
                'snapshot' => '{"memo":{"name":"upload-diagnostic"},"checksum":"unchanged"}',
                'effects' => ['html' => "<div>UTF-8 probe: \xFF</div>"],
            ]],
            'assets' => [],
        ];
        $finish = Livewire\trigger('response', $payload);
        $result = $finish($payload);
        json_encode($result, JSON_THROW_ON_ERROR);
        $report['output_guard_registered_and_working'] = true;
    } catch (Throwable $error) {
        $report['output_guard_registered_and_working'] = false;
        $report['output_probe_error'] = get_class($error).': '.$error->getMessage();
    } finally {
        Illuminate\Support\Facades\Log::swap($logger);
    }
} catch (Throwable $error) {
    $report['bootstrap_error'] = get_class($error).' (code '.$error->getCode().')';
}

// Read at most the last 2 MB. Omit exception arguments, request bodies and SQL.
$logs = glob($root.'/storage/logs/laravel*.log') ?: [];
usort($logs, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
$report['latest_utf8_error_trace'] = [];
foreach (array_slice($logs, 0, 5) as $log) {
    if (! is_readable($log)) {
        continue;
    }
    $handle = fopen($log, 'rb');
    fseek($handle, max(0, filesize($log) - 2 * 1024 * 1024));
    $lines = preg_split('/\r?\n/', stream_get_contents($handle));
    fclose($handle);
    $latest = null;
    foreach ($lines as $index => $line) {
        if (str_contains($line, 'Malformed UTF-8')) {
            $latest = $index;
        }
    }
    if ($latest !== null) {
        $report['error_log_file'] = basename($log);
        if (preg_match('/^\[([0-9:T .+\-Z]+)\]/', $lines[$latest], $match) === 1) {
            $report['error_logged_at'] = $match[1];
        }
        foreach (array_slice($lines, $latest + 1, 45) as $line) {
            if (preg_match('/^(#\d+\s+.+?\(\d+\):\s+[^\(]+)\(/', $line, $match) === 1) {
                $report['latest_utf8_error_trace'][] = $match[1].'()';
            }
        }
        break;
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
