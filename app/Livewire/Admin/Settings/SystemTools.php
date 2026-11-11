<?php

namespace App\Livewire\Admin\Settings;

use App\Models\SiteSetting;
use App\Services\SiteBackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class SystemTools extends Component
{
    public string $frequency = 'manual';

    public string $time = '02:00';

    public string $timezone = 'Asia/Colombo';

    public int $retention = 5;

    public function mount(): void
    {
        Gate::authorize('settings.manage');
        $settings = SiteSetting::current();
        $this->frequency = (string) $settings->getAttribute('backup_frequency');
        $this->time = (string) $settings->getAttribute('backup_time');
        $this->timezone = (string) $settings->getAttribute('backup_timezone');
        $this->retention = (int) $settings->getAttribute('backup_retention');
    }

    public function saveSchedule(): void
    {
        Gate::authorize('settings.manage');
        $this->validate(['frequency' => ['required', 'in:manual,daily,weekly'], 'time' => ['required', 'date_format:H:i'], 'timezone' => ['required', 'timezone'], 'retention' => ['required', 'integer', 'between:1,30']]);
        SiteSetting::current()->forceFill(['backup_frequency' => $this->frequency, 'backup_time' => $this->time, 'backup_timezone' => $this->timezone, 'backup_retention' => $this->retention])->save();
        session()->flash('status', 'Backup schedule saved. Weekly backups run on Mondays.');
    }

    public function createBackup(): void
    {
        Gate::authorize('settings.manage');
        $success = app(SiteBackupService::class)->run(auth()->user());
        session()->flash('status', $success ? 'Backup completed successfully.' : 'Backup could not finish or another backup is running. Check the server log before trying again.');
    }

    public function render(): View
    {
        Gate::authorize('settings.manage');

        return view('livewire.admin.settings.system-tools', ['archives' => app(SiteBackupService::class)->archives(), 'lastRun' => Cache::get('site-backup-status'), 'heartbeat' => Cache::get('site-scheduler-heartbeat'), 'displayTimezone' => in_array($this->timezone, \DateTimeZone::listIdentifiers(), true) ? $this->timezone : 'Asia/Colombo'])->layout('components.layouts.admin', ['title' => 'Backups & System Status']);
    }
}
