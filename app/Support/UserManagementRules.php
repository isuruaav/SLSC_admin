<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class UserManagementRules
{
    /** @var list<string> */
    private const PROTECTED_ROLES = ['Super Administrator', 'Site Administrator'];

    public static function canManageTarget(User $actor, User $target): bool
    {
        if ($actor->hasRole('Super Administrator')) {
            return true;
        }

        if ($target->hasAnyRole(self::PROTECTED_ROLES)) {
            return false;
        }

        // Includes direct permissions as well as permissions inherited from roles.
        foreach ($target->getAllPermissions() as $permission) {
            if (! $actor->can($permission->name)) {
                return false;
            }
        }

        return true;
    }

    public static function assertTargetManageable(User $actor, User $target): void
    {
        abort_unless(
            self::canManageTarget($actor, $target),
            403,
            'You cannot manage this protected account or an account with permissions beyond your own.',
        );
    }

    /** @return list<string> */
    public static function assignableRoleNames(User $actor): array
    {
        if (! $actor->can('users.assign-role')) {
            return [];
        }

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->sortBy(static fn (Role $role): int => RolePresets::order($role->name));

        $names = [];

        foreach ($roles as $role) {
            if (self::canAssignRole($actor, $role)) {
                $names[] = $role->name;
            }
        }

        return $names;
    }

    public static function ensureRoleCanBeAssigned(User $actor, string $roleName): void
    {
        if (in_array($roleName, self::assignableRoleNames($actor), true)) {
            return;
        }

        throw ValidationException::withMessages([
            'role' => 'You cannot assign a protected role or a role with permissions beyond your own.',
        ]);
    }

    public static function ensureRoleChangeAllowed(User $actor, User $target, string $newRole): void
    {
        self::assertTargetManageable($actor, $target);
        self::ensureRoleCanBeAssigned($actor, $newRole);

        if ($actor->is($target) && $target->hasRole('Super Administrator') && $newRole !== 'Super Administrator') {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own Super Administrator role.',
            ]);
        }

        if (
            $target->is_active
            && $target->hasRole('Super Administrator')
            && $newRole !== 'Super Administrator'
            && self::activeSuperAdministratorCount() <= 1
        ) {
            throw ValidationException::withMessages([
                'role' => 'The last active Super Administrator cannot be demoted.',
            ]);
        }
    }

    public static function ensureStatusChangeAllowed(User $actor, User $target, bool $newStatus): void
    {
        self::assertTargetManageable($actor, $target);

        if ($actor->is($target) && ! $newStatus) {
            throw ValidationException::withMessages([
                'user' => 'You cannot disable your own account.',
            ]);
        }

        if (
            $target->is_active
            && ! $newStatus
            && $target->hasRole('Super Administrator')
            && self::activeSuperAdministratorCount() <= 1
        ) {
            throw ValidationException::withMessages([
                'user' => 'The last active Super Administrator cannot be disabled.',
            ]);
        }
    }

    public static function ensurePasswordResetAllowed(User $actor, User $target): void
    {
        self::assertTargetManageable($actor, $target);

        if ($actor->is($target)) {
            throw ValidationException::withMessages([
                'administrator_password' => 'Use your own Security Settings page to change your password.',
            ]);
        }
    }

    public static function ensureDeletionAllowed(User $actor, User $target): void
    {
        self::assertTargetManageable($actor, $target);

        if ($actor->is($target)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if ($target->is_active && $target->hasRole('Super Administrator') && self::activeSuperAdministratorCount() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'The last active Super Administrator cannot be deleted.',
            ]);
        }
    }

    /**
     * Call inside the transaction, before reading or locking the target user.
     * Serialize administrator demotions/disabling across different target rows.
     */
    public static function lockSuperAdministrators(): void
    {
        $role = Role::query()->where('name', 'Super Administrator')->where('guard_name', 'web')->first();

        if (! $role instanceof Role) {
            return;
        }

        User::role($role)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    private static function canAssignRole(User $actor, Role $role): bool
    {
        if ($actor->hasRole('Super Administrator')) {
            return true;
        }

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return false;
        }

        foreach ($role->permissions as $permission) {
            if (! $actor->can($permission->name)) {
                return false;
            }
        }

        return true;
    }

    private static function activeSuperAdministratorCount(): int
    {
        return User::role('Super Administrator', 'web')->where('is_active', true)->count();
    }
}
