<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Throwable;

final class SiteBackupService
{
    /** @return list<array{name: string, size: int, modified: int}> */
    public function archives(): array
    {
        $directory = storage_path('app/backups');
        if (! is_dir($directory)) {
            return [];
        }
        $archives = [];
        foreach (File::files($directory) as $file) {
            if (preg_match('/^backup_[a-zA-Z0-9_-]+\.zip$/', $file->getFilename()) && $file->getSize() > 0) {
                $archives[] = ['name' => $file->getFilename(), 'size' => $file->getSize(), 'modified' => $file->getMTime()];
            }
        }
        usort($archives, fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $archives;
    }

    public function run(?User $actor = null): bool
    {
        $lock = Cache::lock('site-backup-running', 3600);
        if (! $lock->get()) {
            return false;
        }
        try {
            $settings = SiteSetting::current();
            $success = Artisan::call('site:backup', ['--keep' => max(1, min(30, (int) $settings->getAttribute('backup_retention')))]) === 0;
            Cache::forever('site-backup-status', ['success' => $success, 'finished_at' => now()->toIso8601String()]);
            app(AuditLogger::class)->log(event: $success ? 'backups.created' : 'backups.failed', description: $success ? 'Site database and uploaded files backed up.' : 'Site backup failed. See the server log for details.', actor: $actor);

            return $success;
        } catch (Throwable $exception) {
            report($exception);
            Cache::forever('site-backup-status', ['success' => false, 'finished_at' => now()->toIso8601String()]);

            return false;
        } finally {
            $lock->release();
        }
    }

    public function runScheduled(): void
    {
        Cache::put('site-scheduler-heartbeat', now()->toIso8601String(), 600);
        $settings = SiteSetting::current();
        $frequency = $settings->getAttribute('backup_frequency');
        if (! in_array($frequency, ['daily', 'weekly'], true)) {
            return;
        }
        $now = now((string) $settings->getAttribute('backup_timezone'));
        if ($now->format('H:i') !== $settings->getAttribute('backup_time')) {
            return;
        }
        if ($frequency === 'weekly' && ! $now->isMonday()) {
            return;
        }
        if (Cache::add('scheduled-backup-'.$now->format('Y-m-d'), true, 86400)) {
            $this->run();
        }
    }
}
