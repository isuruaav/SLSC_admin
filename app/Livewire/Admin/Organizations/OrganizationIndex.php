<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class OrganizationIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $category = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(as: 'sort', except: 'sort_order')]
    public string $sortField = 'sort_order';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDirection = 'asc';

    public int $perPage = 15;

    public ?int $editingShortNameId = null;
    public string $editingShortNameValue = '';

    public function startEditShortName(int $id, ?string $currentValue = null): void
    {
        Gate::authorize('organizations.update');
        $this->editingShortNameId = $id;
        $this->editingShortNameValue = (string) $currentValue;
    }

    public function saveShortName(): void
    {
        Gate::authorize('organizations.update');

        if ($this->editingShortNameId) {
            $org = Organization::findOrFail($this->editingShortNameId);
            $newVal = trim($this->editingShortNameValue) ?: null;
            $org->update(['short_name' => $newVal]);
            session()->flash('status', "Updated short name for [{$org->titleForLocale('en')}] to '" . ($newVal ?: 'None') . "'.");
            $this->editingShortNameId = null;
        }
    }

    public function cancelEditShortName(): void
    {
        $this->editingShortNameId = null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function togglePublish(int $id): void
    {
        Gate::authorize('organizations.publish');

        $org = Organization::findOrFail($id);
        if ($org->status === OrganizationStatus::Published) {
            $org->update([
                'status' => OrganizationStatus::Draft,
                'published_at' => null,
            ]);
            session()->flash('status', "Unpublished [{$org->titleForLocale('en')}].");
        } else {
            $org->update([
                'status' => OrganizationStatus::Published,
                'published_at' => now(),
            ]);
            session()->flash('status', "Published [{$org->titleForLocale('en')}].");
        }
    }

    public function deleteOrganization(int $id): void
    {
        Gate::authorize('organizations.delete');

        $org = Organization::findOrFail($id);
        $title = $org->titleForLocale('en');
        $org->delete();

        session()->flash('status', "Organization [{$title}] deleted successfully.");
    }

    public function moveUp(int $id): void
    {
        Gate::authorize('organizations.update');

        $org = Organization::findOrFail($id);
        $previous = Organization::where('sort_order', '<', $org->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previous) {
            $prevOrder = $previous->sort_order;
            $previous->update(['sort_order' => $org->sort_order]);
            $org->update(['sort_order' => $prevOrder]);
        }
    }

    public function moveDown(int $id): void
    {
        Gate::authorize('organizations.update');

        $org = Organization::findOrFail($id);
        $next = Organization::where('sort_order', '>', $org->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($next) {
            $nextOrder = $next->sort_order;
            $next->update(['sort_order' => $org->sort_order]);
            $org->update(['sort_order' => $nextOrder]);
        }
    }

    public function render(): View
    {
        Gate::authorize('organizations.view');

        $query = Organization::query()
            ->with(['category', 'translations', 'contentBlocks'])
            ->withCount(['contentBlocks']);

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhereHas('translations', function (Builder $tQuery) use ($search): void {
                        $tQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('summary', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->category !== 'all') {
            $query->where('category_id', (int) $this->category);
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        $allowedSorts = ['sort_order', 'slug', 'updated_at', 'created_at', 'status'];
        $sort = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'sort_order';
        $direction = strtolower($this->sortDirection) === 'desc' ? 'desc' : 'asc';

        $organizations = $query->orderBy($sort, $direction)->paginate($this->perPage);
        $categories = OrganizationCategory::ordered()->get();

        return view('livewire.admin.organizations.organization-index', [
            'organizations' => $organizations,
            'categories' => $categories,
        ])->layout('components.layouts.admin', ['title' => 'Organization Management']);
    }
}

