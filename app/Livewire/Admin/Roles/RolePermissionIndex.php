<?php

namespace App\Livewire\Admin\Roles;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DefaultRoleService;
use App\Support\PermissionCatalog;
use App\Support\RolePresets;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionIndex extends Component
{
    #[Locked]
    public ?int $selectedRoleId = null;

    /** @var list<string> */
    public array $selectedPermissions = [];

    public string $newRoleName = '';

    public string $newRoleTemplate = 'Content Editor';

    public string $permissionTemplate = 'Content Editor';

    public function mount(): void
    {
        Gate::authorize('roles.manage');

        $role = Role::query()
            ->where('guard_name', 'web')
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', ['Content Editor'])
            ->orderBy('name')
            ->first();

        $this->selectedRoleId = $role instanceof Role
            ? (int) $role->getKey()
            : null;

        $this->loadSelectedPermissions();
        $this->dispatch('role-editor-reset');
    }

    public function selectRole(int $roleId): void
    {
        Gate::authorize('roles.manage');

        Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($roleId);

        $this->selectedRoleId = $roleId;
        $this->resetValidation();
        $this->loadSelectedPermissions();
        $this->dispatch('role-editor-reset');
    }

    public function createRole(): void
    {
        $actor = $this->authorizeRoleChanges();
        $this->newRoleName = trim(strip_tags($this->newRoleName));

        $this->validate([
            'newRoleName' => [
                'required',
                'string',
                'max:100',
                Rule::unique(Role::class, 'name')->where('guard_name', 'web'),
            ],
            'newRoleTemplate' => ['required', 'string', Rule::in(['blank', ...$this->templateNames()])],
        ]);

        if (in_array(mb_strtolower($this->newRoleName), [
            'super administrator',
            'site administrator',
            'super admin',
            'site admin',
        ], true)) {
            throw ValidationException::withMessages([
                'newRoleName' => 'This name is reserved for a system administrator role.',
            ]);
        }

        $permissions = $this->templatePermissions($this->newRoleTemplate);
        $role = DB::transaction(function () use ($actor, $permissions): Role {
            $role = Role::query()->create([
                'name' => $this->newRoleName,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($permissions);

            app(AuditLogger::class)->log(
                event: 'roles.created',
                description: 'A role was created.',
                actor: $actor,
                subject: $role,
                newValues: ['name' => $role->name, 'template' => $this->newRoleTemplate, 'permissions' => $permissions],
            );

            return $role;
        });

        $this->selectedRoleId = (int) $role->getKey();
        $this->loadSelectedPermissions();
        $this->newRoleName = '';
        $this->resetValidation();
        $this->dispatch('role-editor-reset');

        session()->flash('status', 'Role created successfully. Review its access below, then assign it to users.');
    }

    public function installRecommendedRoles(): void
    {
        $actor = $this->authorizeRoleChanges();
        $created = app(DefaultRoleService::class)->install(actor: $actor);
        if ($this->selectedRoleId === null) {
            $this->selectedRoleId = (int) Role::findByName('Content Editor', 'web')->getKey();
        }
        $this->loadSelectedPermissions();
        $this->dispatch('role-editor-reset');
        session()->flash('status', $created === []
            ? 'All recommended roles are already available. Existing permissions were kept.'
            : 'Recommended roles added. Existing permissions and user assignments were kept.');
    }

    public function applyTemplate(): void
    {
        $this->assertEditableRole();
        $this->validate(['permissionTemplate' => ['required', 'string', Rule::in($this->templateNames())]]);
        $this->selectedPermissions = $this->templatePermissions($this->permissionTemplate);
        $this->resetValidation();
        $this->dispatch('role-editor-changed');
    }

    public function discardChanges(): void
    {
        Gate::authorize('roles.manage');
        $this->loadSelectedPermissions();
        $this->resetValidation();
        $this->dispatch('role-editor-reset');
    }

    public function togglePermission(string $name): void
    {
        $this->assertEditableRole();
        $available = $this->allPermissionNames();
        abort_unless(in_array($name, $available, true), 422);
        $this->selectedPermissions = in_array($name, $this->selectedPermissions, true)
            ? PermissionCatalog::withoutDependants($this->selectedPermissions, [$name], $available)
            : PermissionCatalog::withDependencies([...$this->selectedPermissions, $name], $available);
        $this->resetValidation();
        $this->dispatch('role-editor-changed');
    }

    public function togglePermissionGroup(string $group): void
    {
        $this->assertEditableRole();
        $available = $this->allPermissionNames();
        $groupPermissions = array_values(array_filter($available, static fn (string $name): bool => PermissionCatalog::group($name) === $group));
        abort_if($groupPermissions === [], 422);
        $this->selectedPermissions = array_diff($groupPermissions, $this->selectedPermissions) === []
            ? PermissionCatalog::withoutDependants($this->selectedPermissions, $groupPermissions, $available)
            : PermissionCatalog::withDependencies([...$this->selectedPermissions, ...$groupPermissions], $available);
        $this->resetValidation();
        $this->dispatch('role-editor-changed');
    }

    public function savePermissions(): void
    {
        $actor = $this->authorizeRoleChanges();
        $role = $this->selectedRole();

        if ($role->name === 'Super Administrator') {
            throw ValidationException::withMessages([
                'role' => 'Super Administrator has full access through the system authorization rule and cannot be changed here.',
            ]);
        }

        $valid = $this->allPermissionNames();
        $this->validate([
            'selectedPermissions' => ['present', 'array', 'max:'.count($valid)],
            'selectedPermissions.*' => ['required', 'string', 'distinct', Rule::in($valid)],
        ]);

        $permissions = $this->selectedPermissions;
        sort($permissions);
        $this->assertPermissionDependencies($permissions);

        $registrar = app(PermissionRegistrar::class);

        try {
            DB::transaction(function () use ($actor, $role, $permissions): void {
                $lockedRole = Role::query()
                    ->where('guard_name', 'web')
                    ->whereKey($role->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                // Re-check the protected name inside the transaction.
                if ($lockedRole->name === 'Super Administrator') {
                    throw ValidationException::withMessages([
                        'role' => 'The Super Administrator role is protected.',
                    ]);
                }

                $old = $this->rolePermissionNames($lockedRole);
                $lockedRole->syncPermissions($permissions);

                app(AuditLogger::class)->log(
                    event: 'roles.permissions-updated',
                    description: 'Role permissions were updated.',
                    actor: $actor,
                    subject: $lockedRole,
                    oldValues: ['permissions' => $old],
                    newValues: ['permissions' => $permissions],
                );
            });
        } finally {
            $registrar->forgetCachedPermissions();
        }

        $this->loadSelectedPermissions();
        $this->dispatch('role-editor-reset');
        session()->flash('status', 'Role permissions updated successfully.');
    }

    public function render(): View
    {
        Gate::authorize('roles.manage');

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $grouped = $permissions->groupBy(static fn (Permission $permission): string => PermissionCatalog::group($permission->name));
        $roles = Role::query()->where('guard_name', 'web')->withCount(['users', 'permissions'])->orderBy('name')->get()
            ->sortBy(static fn (Role $role): int => RolePresets::order($role->name));

        return view('livewire.admin.roles.role-permission-index', [
            'roles' => $roles,
            'missingRecommendedRoles' => array_values(array_diff(array_keys(RolePresets::all()), $roles->pluck('name')->all())),
            'templates' => array_intersect_key(RolePresets::all(), array_flip($this->templateNames())),
            'groupedPermissions' => $grouped,
            'canManageRoles' => $this->actor()->hasRole('Super Administrator'),
            'selectedRole' => $this->selectedRoleId === null
                ? null
                : $this->selectedRole(),
        ])->layout('components.layouts.admin', ['title' => 'Roles & Permissions']);
    }

    private function loadSelectedPermissions(): void
    {
        if ($this->selectedRoleId === null) {
            $this->selectedPermissions = [];

            return;
        }

        $role = $this->selectedRole();
        $this->selectedPermissions = $role->name === 'Super Administrator'
            ? $this->allPermissionNames()
            : $this->rolePermissionNames($role);
        $this->permissionTemplate = in_array($role->name, $this->templateNames(), true) ? $role->name : 'Content Editor';
    }

    private function selectedRole(): Role
    {
        if ($this->selectedRoleId === null) {
            throw ValidationException::withMessages([
                'role' => 'Select a role first.',
            ]);
        }

        return Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($this->selectedRoleId);
    }

    /** @return list<string> */
    private function allPermissionNames(): array
    {
        $names = [];

        foreach (Permission::query()->where('guard_name', 'web')->orderBy('name')->get(['name']) as $permission) {
            $names[] = $permission->name;
        }

        return $names;
    }

    /** @return list<string> */
    private function rolePermissionNames(Role $role): array
    {
        $names = [];

        foreach ($role->permissions as $permission) {
            $names[] = $permission->name;
        }

        sort($names);

        return $names;
    }

    /** @param list<string> $permissions */
    private function assertPermissionDependencies(array $permissions): void
    {
        if ($permissions !== [] && ! in_array('admin.access', $permissions, true)) {
            throw ValidationException::withMessages([
                'selectedPermissions' => 'Enable Open the admin workspace before granting other access.',
            ]);
        }

        $available = $this->allPermissionNames();
        foreach ($permissions as $permission) {
            foreach (PermissionCatalog::dependencies($permission, $available) as $dependency) {
                if (! in_array($dependency, $permissions, true)) {
                    throw ValidationException::withMessages([
                        'selectedPermissions' => 'Enable '.PermissionCatalog::groupLabel(PermissionCatalog::group($dependency)).' — '.PermissionCatalog::label($dependency).' before granting '.PermissionCatalog::label($permission).'.',
                    ]);
                }
            }
        }
    }

    /** @return list<string> */
    private function templateNames(): array
    {
        return array_values(array_diff(array_keys(RolePresets::all()), ['Super Administrator']));
    }

    /** @return list<string> */
    private function templatePermissions(string $name): array
    {
        return PermissionCatalog::withDependencies(RolePresets::all()[$name]['permissions'] ?? [], $this->allPermissionNames());
    }

    private function assertEditableRole(): void
    {
        $this->authorizeRoleChanges();
        if ($this->selectedRole()->name === 'Super Administrator') {
            throw ValidationException::withMessages(['role' => 'The Super Administrator role always has full access and cannot be changed here.']);
        }
    }

    private function authorizeRoleChanges(): User
    {
        Gate::authorize('roles.manage');
        $actor = $this->actor();

        abort_unless(
            $actor->hasRole('Super Administrator'),
            403,
            'Only a Super Administrator may create roles or change their permissions.',
        );

        return $actor;
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
