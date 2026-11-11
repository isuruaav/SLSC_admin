<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'first-appointments.view',
            'first-appointments.manage',
        ];

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        DB::transaction(function () use ($permissions): void {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            foreach (['Super Administrator', 'Site Administrator'] as $name) {
                $role = Role::query()->where('guard_name', 'web')->where('name', $name)->first();
                if ($role instanceof Role) {
                    $role->givePermissionTo($permissions);
                }
            }

            $publisher = Role::query()->where('guard_name', 'web')->where('name', 'Publisher')->first();
            if ($publisher instanceof Role) {
                $publisher->givePermissionTo($permissions);
            }

            $editor = Role::query()->where('guard_name', 'web')->where('name', 'Content Editor')->first();
            if ($editor instanceof Role) {
                $editor->givePermissionTo($permissions);
            }
        });

        $registrar->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissions = [
            'first-appointments.view',
            'first-appointments.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->where('name', $permission)->where('guard_name', 'web')->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

