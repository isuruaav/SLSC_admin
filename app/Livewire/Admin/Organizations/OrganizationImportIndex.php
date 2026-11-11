<?php

namespace App\Livewire\Admin\Organizations;

use App\Models\Organization;
use App\Models\OrganizationRevision;
use App\Models\User;
use App\Services\OrganizationImportService;
use App\Services\OrganizationRevisionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class OrganizationImportIndex extends Component
{
    /** @var array<int, string> */
    public array $selectedKeys = [];

    public bool $selectAll = false;

    public bool $overwriteExisting = false;

    public ?string $previewKey = null;

    /** @var array<string, mixed>|null */
    public ?array $previewData = null;

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $service = app(OrganizationImportService::class);
            $this->selectedKeys = array_column($service->discoverUnits(), 'key');
        } else {
            $this->selectedKeys = [];
        }
    }

    public function importSingle(string $key): void
    {
        Gate::authorize('organizations.import');

        $service = app(OrganizationImportService::class);
        $result = $service->importUnit($key, $this->overwriteExisting, $this->actor()->id);

        if ($result['success']) {
            session()->flash('status', $result['message']);
        } else {
            session()->flash('warning', $result['message']);
        }
    }

    public function importSelected(): void
    {
        Gate::authorize('organizations.import');

        if (empty($this->selectedKeys)) {
            session()->flash('warning', 'Please select at least one organization to import.');

            return;
        }

        $service = app(OrganizationImportService::class);
        $result = $service->importMultiple($this->selectedKeys, $this->overwriteExisting, $this->actor()->id);

        $msg = "Imported {$result['imported']} unit(s). Skipped {$result['skipped']}.";
        if (! empty($result['errors'])) {
            $msg .= ' Details: '.implode(' | ', array_slice($result['errors'], 0, 3));
        }

        session()->flash('status', $msg);
        $this->selectedKeys = [];
        $this->selectAll = false;
    }

    public function importAll(): void
    {
        Gate::authorize('organizations.import');

        $service = app(OrganizationImportService::class);
        $result = $service->importAll($this->overwriteExisting, $this->actor()->id);

        session()->flash('status', "Imported {$result['imported']} unit(s). Skipped {$result['skipped']}.");
        $this->selectedKeys = [];
        $this->selectAll = false;
    }

    public function previewUnit(string $key): void
    {
        Gate::authorize('organizations.import');

        $service = app(OrganizationImportService::class);
        $all = $service->getReferenceData();
        if (isset($all[$key])) {
            $this->previewKey = $key;
            $this->previewData = $all[$key];
        }
    }

    public function closePreview(): void
    {
        $this->previewKey = null;
        $this->previewData = null;
    }

    public function render(): View
    {
        Gate::authorize('organizations.import');

        $service = app(OrganizationImportService::class);
        $discovered = $service->discoverUnits();

        return view('livewire.admin.organizations.organization-import-index', [
            'discovered' => $discovered,
            'previewExisting' => $this->previewData ? Organization::with('translations')->withCount('contentBlocks')->where('slug', $this->previewData['slug'])->first() : null,
            'revisions' => OrganizationRevision::query()->latest('id')->limit(10)->get(),
        ])->layout('components.layouts.admin', ['title' => 'Import Legacy Organizations']);
    }

    public function restoreRevision(int $id): void
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);
        app(OrganizationRevisionService::class)->restore($id, $actor);
        session()->flash('status', 'Previous organization content restored as a draft.');
    }

    private function actor(): User
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
