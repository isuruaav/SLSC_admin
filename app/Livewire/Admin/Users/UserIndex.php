<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\UserDeletionService;
use App\Support\UserManagementRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

final class UserIndex extends Component
{
    use WithPagination;

    /** @var list<string> */
    private const SORTABLE_FIELDS = ['name', 'email', 'is_active', 'last_login_at', 'created_at'];

    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 15, 25, 50];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $role = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(except: 'name')]
    public string $sortField = 'name';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    public int $perPage = 15;

    public function mount(): void
    {
        Gate::authorize('users.view');
        $this->normaliseQueryParameters();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->normaliseRole();
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->normaliseStatus();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->normalisePerPage();
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->role = 'all';
        $this->status = 'all';
        $this->resetPage();
    }

    public function sort(string $field): void
    {
        if (! in_array($field, self::SORTABLE_FIELDS, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function toggleActive(int $userId): void
    {
        Gate::authorize('users.view');
        Gate::authorize('users.update');
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        $target = DB::transaction(function () use ($actor, $userId): User {
            UserManagementRules::lockSuperAdministrators();

            $target = User::query()->with('roles')->lockForUpdate()->findOrFail($userId);
            $oldStatus = (bool) $target->is_active;
            $newStatus = ! $oldStatus;

            UserManagementRules::ensureStatusChangeAllowed($actor, $target, $newStatus);

            $attributes = ['is_active' => $newStatus, 'updated_by' => $actor->id];

            if (! $newStatus) {
                $attributes['remember_token'] = Str::random(60);
            }

            $target->forceFill($attributes)->save();

            // Preserve the existing database-backed session invalidation.
            if (! $newStatus) {
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }

            app(AuditLogger::class)->log(
                event: $newStatus ? 'users.activated' : 'users.disabled',
                description: $newStatus ? 'Administrator account activated.' : 'Administrator account disabled.',
                actor: $actor,
                subject: $target,
                oldValues: ['is_active' => $oldStatus],
                newValues: ['is_active' => $newStatus, 'sessions_terminated' => ! $newStatus],
            );

            return $target;
        });

        session()->flash('status', $target->is_active
            ? "{$target->name}'s account was activated."
            : "{$target->name}'s account was disabled.");
    }

    public function deleteUser(int $userId): void
    {
        Gate::authorize('users.view');
        Gate::authorize('users.delete');
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        $target = app(UserDeletionService::class)->delete($actor, $userId);
        $this->resetValidation();
        $this->resetPage();
        session()->flash('status', "{$target->name}'s account was deleted. Existing website content was retained.");
    }

    public function render(): View
    {
        Gate::authorize('users.view');
        $this->normaliseQueryParameters();

        $query = User::query()->with(['roles:id,name', 'creator:id,name']);
        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($this->role !== 'all') {
            $query->role($this->role);
        }

        if ($this->status === 'active') {
            $query->where('is_active', true);
        }

        if ($this->status === 'disabled') {
            $query->where('is_active', false);
        }

        $users = $query
            ->orderBy($this->normalisedSortField(), $this->normalisedSortDirection())
            ->paginate($this->perPage);

        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name');

        return view('livewire.admin.users.user-index', compact('users', 'roles'))
            ->layout('components.layouts.admin', ['title' => 'Users']);
    }

    private function normaliseQueryParameters(): void
    {
        $this->sortField = $this->normalisedSortField();
        $this->sortDirection = $this->normalisedSortDirection();
        $this->normalisePerPage();
        $this->normaliseStatus();
        $this->normaliseRole();
    }

    private function normalisePerPage(): void
    {
        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 15;
        }
    }

    private function normaliseStatus(): void
    {
        if (! in_array($this->status, ['all', 'active', 'disabled'], true)) {
            $this->status = 'all';
        }
    }

    private function normaliseRole(): void
    {
        if ($this->role === 'all') {
            return;
        }

        if (! Role::query()->where('guard_name', 'web')->where('name', $this->role)->exists()) {
            $this->role = 'all';
        }
    }

    private function normalisedSortField(): string
    {
        return in_array($this->sortField, self::SORTABLE_FIELDS, true) ? $this->sortField : 'name';
    }

    /** @return 'asc'|'desc' */
    private function normalisedSortDirection(): string
    {
        return $this->sortDirection === 'desc' ? 'desc' : 'asc';
    }
}
