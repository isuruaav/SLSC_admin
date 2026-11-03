<?php

namespace App\Services;

use App\Models\User;
use App\Support\RolePresets;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class DefaultRoleService
{
    /** @return list<string> Names of newly created roles. */
    public function install(bool $resetExisting = false, ?User $actor = null): array
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        try {
            return DB::transaction(function () use ($resetExisting, $actor): array {
                foreach (RolePresets::permissions() as $name) {
                    Permission::findOrCreate($name, 'web');
                }

                $created = [];
                foreach (RolePresets::all() as $name => $preset) {
                    $role = Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                    $isNew = $role->wasRecentlyCreated;

                    if ($isNew || $resetExisting) {
                        $role->syncPermissions($preset['permissions']);
                    }

                    if ($isNew) {
                        $created[] = $name;
                    }
                    if ($isNew && ! $resetExisting) {
                        app(AuditLogger::class)->log(
                            event: 'roles.created',
                            description: 'A recommended role was created.',
                            actor: $actor,
                            subject: $role,
                            newValues: ['name' => $name, 'template' => $name, 'permissions' => $preset['permissions']],
                        );
                    }
                }

                return $created;
            });
        } finally {
            $registrar->forgetCachedPermissions();
        }
    }
}
