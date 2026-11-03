<?php

namespace App\Console\Commands;

use App\Services\DefaultRoleService;
use Illuminate\Console\Command;

final class SetupRoles extends Command
{
    protected $signature = 'roles:setup';

    protected $description = 'Create missing recommended CMS roles while preserving existing roles, grants and user assignments';

    public function handle(DefaultRoleService $roles): int
    {
        $created = $roles->install();
        $this->info($created === [] ? 'All six recommended roles are already available.' : 'Created: '.implode(', ', $created));
        $this->line('Existing role permissions and user assignments were preserved.');

        return self::SUCCESS;
    }
}
