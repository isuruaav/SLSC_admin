<?php

namespace App\Livewire\Admin\SchoolLeaders;

use App\Enums\SchoolLeadershipRankGroup;
use App\Models\SchoolLeadershipPosition;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

final class SchoolLeadershipPositionIndex extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public bool $showEditor = false;

    public string $nameEn = '';

    public string $systemKey = '';

    #[Locked]
    public bool $systemKeyManuallyEdited = false;

    public string $nameSi = '';

    public string $rankGroup = '';

    public int $sortOrder = 100;

    public bool $showOnHome = false;

    public bool $isActive = true;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $homepageFilter = 'all';

    public string $rankGroupFilter = 'all';

    public int $perPage = 10;

    public string $sortField = 'sort_order';

    public string $sortDirection = 'asc';

    public function mount(): void
    {
        Gate::authorize('school-leaders.view');

        $this->resetForm();
    }

    public function startCreate(): void
    {
        Gate::authorize('school-leaders.update');

        $this->resetForm();
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('school-leaders.view');
        Gate::authorize('school-leaders.update');

        $position = SchoolLeadershipPosition::query()
            ->findOrFail($id);

        $this->editingId = $position->id;
        $this->nameEn = $position->name_en;
        $this->systemKey = $position->key;
        $this->systemKeyManuallyEdited = true;
        $this->nameSi = $position->name_si;
        $this->rankGroup = (string) $position->getRawOriginal(
            'rank_group',
        );
        $this->sortOrder = $position->sort_order;
        $this->showOnHome = $position->show_on_home;
        $this->isActive = $position->is_active;

        $this->resetValidation();
        $this->showEditor = true;
    }

    public function closeEditor(): void
    {
        Gate::authorize('school-leaders.view');
        Gate::authorize('school-leaders.update');

        $this->showEditor = false;
        $this->resetForm();
    }

    public function updatedNameEn(): void
    {
        if ($this->editingId === null && ! $this->systemKeyManuallyEdited) {
            $this->systemKey = trim($this->nameEn) === ''
                ? ''
                : $this->generateUniqueKey($this->nameEn);
        }
    }

    public function updatedSystemKey(): void
    {
        $this->systemKeyManuallyEdited = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        if (! in_array(
            $this->statusFilter,
            [
                'all',
                'active',
                'inactive',
            ],
            true,
        )) {
            $this->statusFilter = 'all';
        }

        $this->resetPage();
    }

    public function updatedHomepageFilter(): void
    {
        if (! in_array(
            $this->homepageFilter,
            [
                'all',
                'yes',
                'no',
            ],
            true,
        )) {
            $this->homepageFilter = 'all';
        }

        $this->resetPage();
    }

    public function updatedRankGroupFilter(): void
    {
        if (
            $this->rankGroupFilter !== 'all'
            && SchoolLeadershipRankGroup::tryFrom(
                $this->rankGroupFilter,
            ) === null
        ) {
            $this->rankGroupFilter = 'all';
        }

        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array(
            $this->perPage,
            [
                10,
                25,
                50,
            ],
            true,
        )) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array(
            $field,
            [
                'appointment',
                'rank_group',
                'leaders',
                'homepage',
                'status',
                'sort_order',
            ],
            true,
        )) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc'
                ? 'desc'
                : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function resetTableFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->homepageFilter = 'all';
        $this->rankGroupFilter = 'all';

        $this->resetPage();
    }

    public function save(): void
    {
        Gate::authorize('school-leaders.view');
        Gate::authorize('school-leaders.update');

        $this->systemKey = trim($this->systemKey);
        if ($this->editingId === null && $this->systemKey === '') {
            $this->systemKey = $this->generateUniqueKey($this->nameEn);
        }

        $validated = $this->validate([
            'systemKey' => [
                'required',
                'string',
                'max:50',
                'regex:/\A[a-z0-9]+(?:_[a-z0-9]+)*\z/',
                Rule::unique('school_leadership_positions', 'key')->ignore($this->editingId),
            ],
            'nameEn' => [
                'required',
                'string',
                'max:180',
            ],
            'nameSi' => [
                'required',
                'string',
                'max:220',
            ],
            'rankGroup' => [
                'required',
                'string',
                Rule::enum(SchoolLeadershipRankGroup::class),
            ],
            'sortOrder' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],
            'showOnHome' => [
                'boolean',
            ],
            'isActive' => [
                'boolean',
            ],
        ], [
            'systemKey.regex' => 'Use lowercase letters, numbers and single underscores between words.',
            'systemKey.unique' => 'This system key is already used by another appointment type.',
        ], [
            'systemKey' => 'system key',
        ]);

        $actor = $this->actor();
        $creating = $this->editingId === null;

        DB::transaction(function () use (
            $validated,
            $actor,
            $creating,
        ): SchoolLeadershipPosition {
            $position = $this->editingId === null
                ? new SchoolLeadershipPosition
                : SchoolLeadershipPosition::query()
                    ->lockForUpdate()
                    ->findOrFail($this->editingId);

            $oldValues = $position->exists ? $position->getAttributes() : [];
            $keyChanged = $position->exists && $position->key !== $validated['systemKey'];

            $position->fill([
                'key' => $validated['systemKey'],
                'name_en' => trim($validated['nameEn']),
                'name_si' => trim($validated['nameSi']),
                'rank_group' => $validated['rankGroup'],
                'sort_order' => $validated['sortOrder'],
                'show_on_home' => $validated['showOnHome'],
                'is_active' => $validated['isActive'],
            ]);

            $position->save();

            if ($keyChanged) {
                $position->leaders()->update(['role_key' => $position->key]);
            }

            app(AuditLogger::class)->log(
                event: $creating
                    ? 'settings.school-leadership-position-created'
                    : 'settings.school-leadership-position-updated',
                description: $creating
                    ? 'A School Leadership appointment type was created.'
                    : 'A School Leadership appointment type was updated.',
                actor: $actor,
                subject: $position,
                oldValues: $oldValues,
                newValues: $position->getAttributes(),
            );

            return $position;
        });

        session()->flash(
            'status',
            $creating
                ? 'Appointment type created successfully.'
                : 'Appointment type updated successfully.',
        );

        $this->closeEditor();
    }

    public function delete(int $id): void
    {
        Gate::authorize('school-leaders.view');
        Gate::authorize('school-leaders.update');

        $this->resetValidation('delete');
        $actor = $this->actor();

        DB::transaction(function () use ($id, $actor): void {
            $position = SchoolLeadershipPosition::query()->lockForUpdate()->findOrFail($id);

            if ($position->leaders()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'This appointment type has assigned leaders. Reassign or delete those leadership appointments before deleting the type.',
                ]);
            }

            $oldValues = $position->getAttributes();
            $position->delete();

            app(AuditLogger::class)->log(
                event: 'settings.school-leadership-position-deleted',
                description: 'A School Leadership appointment type was deleted.',
                actor: $actor,
                subject: $position,
                oldValues: $oldValues,
            );
        });

        if ($this->editingId === $id) {
            $this->closeEditor();
        }

        $this->resetPage();
        session()->flash('status', 'Appointment type deleted successfully.');
    }

    public function render(): View
    {
        Gate::authorize('school-leaders.view');

        $positionsQuery = SchoolLeadershipPosition::query()
            ->withCount('leaders');

        $search = trim(
            $this->search,
        );

        if ($search !== '') {
            $like = '%'.$search.'%';

            $positionsQuery->where(
                function (Builder $query) use ($like): void {
                    $query
                        ->where(
                            'name_en',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'name_si',
                            'like',
                            $like,
                        )
                        ->orWhere(
                            'key',
                            'like',
                            $like,
                        );
                },
            );
        }

        if ($this->statusFilter === 'active') {
            $positionsQuery->where(
                'is_active',
                true,
            );
        } elseif ($this->statusFilter === 'inactive') {
            $positionsQuery->where(
                'is_active',
                false,
            );
        }

        if ($this->homepageFilter === 'yes') {
            $positionsQuery->where(
                'show_on_home',
                true,
            );
        } elseif ($this->homepageFilter === 'no') {
            $positionsQuery->where(
                'show_on_home',
                false,
            );
        }

        if (
            $this->rankGroupFilter !== 'all'
            && SchoolLeadershipRankGroup::tryFrom(
                $this->rankGroupFilter,
            ) instanceof SchoolLeadershipRankGroup
        ) {
            $positionsQuery->where(
                'rank_group',
                $this->rankGroupFilter,
            );
        }

        $sortDirection = $this->sortDirection === 'desc'
            ? 'desc'
            : 'asc';

        match ($this->sortField) {
            'appointment' => $positionsQuery->orderBy(
                'name_en',
                $sortDirection,
            ),
            'rank_group' => $positionsQuery->orderBy(
                'rank_group',
                $sortDirection,
            ),
            'leaders' => $positionsQuery->orderBy(
                'leaders_count',
                $sortDirection,
            ),
            'homepage' => $positionsQuery->orderBy(
                'show_on_home',
                $sortDirection,
            ),
            'status' => $positionsQuery->orderBy(
                'is_active',
                $sortDirection,
            ),
            default => $positionsQuery->orderBy(
                'sort_order',
                $sortDirection,
            ),
        };

        if ($this->sortField !== 'sort_order') {
            $positionsQuery->orderBy(
                'sort_order',
            );
        }

        $positionsQuery->orderBy('id');

        if (! in_array(
            $this->perPage,
            [
                10,
                25,
                50,
            ],
            true,
        )) {
            $this->perPage = 10;
        }

        /** @var view-string $viewName */
        $viewName = 'livewire.admin.school-leaders.school-leadership-position-index';

        return view(
            $viewName,
            [
                'positions' => $positionsQuery->paginate(
                    $this->perPage,
                ),
                'rankGroups' => SchoolLeadershipRankGroup::cases(),
            ],
        )->layout(
            'components.layouts.admin',
            [
                'title' => 'Leadership Appointment Types',
            ],
        );
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->nameEn = '';
        $this->systemKey = '';
        $this->systemKeyManuallyEdited = false;
        $this->nameSi = '';
        $this->rankGroup = '';
        $this->sortOrder = 100;
        $this->showOnHome = false;
        $this->isActive = true;

        $this->resetValidation();
    }

    private function generateUniqueKey(string $name): string
    {
        $base = rtrim(Str::substr(Str::slug($name, '_'), 0, 50), '_');

        if ($base === '') {
            $base = 'position';
        }

        $key = $base;
        $number = 2;

        while (
            SchoolLeadershipPosition::query()
                ->where('key', $key)
                ->exists()
        ) {
            $suffix = '_'.$number;
            $key = rtrim(Str::substr($base, 0, 50 - strlen($suffix)), '_').$suffix;
            $number++;
        }

        return $key;
    }

    private function actor(): User
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User,
            403,
        );

        return $user;
    }
}
