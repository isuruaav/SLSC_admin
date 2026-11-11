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
            'organizations.view',
            'organizations.create',
            'organizations.update',
            'organizations.delete',
            'organizations.publish',
            'organizations.import',
            'organizations.categories.manage',
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
                $publisher->givePermissionTo([
                    'organizations.view',
                    'organizations.create',
                    'organizations.update',
                    'organizations.publish',
                    'organizations.categories.manage',
                ]);
            }

            $editor = Role::query()->where('guard_name', 'web')->where('name', 'Content Editor')->first();
            if ($editor instanceof Role) {
                $editor->givePermissionTo([
                    'organizations.view',
                    'organizations.create',
                    'organizations.update',
                ]);
            }
        });

        $registrar->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissions = [
            'organizations.view',
            'organizations.create',
            'organizations.update',
            'organizations.delete',
            'organizations.publish',
            'organizations.import',
            'organizations.categories.manage',
        ];

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        Permission::query()->where('guard_name', 'web')->whereIn('name', $permissions)->delete();

        $registrar->forgetCachedPermissions();
    }
};

