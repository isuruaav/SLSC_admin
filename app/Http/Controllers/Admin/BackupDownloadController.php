<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class BackupDownloadController
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        Gate::authorize('settings.manage');
        abort_unless(preg_match('/^backup_[a-zA-Z0-9_-]+\.zip$/', $filename) === 1, 404);
        $root = realpath(storage_path('app/backups'));
        $path = realpath(storage_path('app/backups/'.$filename));
        abort_unless($root !== false && $path !== false && dirname($path) === $root && is_file($path), 404);

        return response()->download($path, $filename, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
