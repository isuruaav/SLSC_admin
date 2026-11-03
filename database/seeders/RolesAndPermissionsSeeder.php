<?php

namespace Database\Seeders;

use App\Services\DefaultRoleService;
use Illuminate\Database\Seeder;

final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Explicit reseeding restores defaults; roles:setup only creates missing roles.
        app(DefaultRoleService::class)->install(resetExisting: true);
    }
}
