<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PatchThemeLayoutSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:add-search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inject search placeholders and modernize navbar / dropdown CSS (desktop + mobile responsive) in theme layouts.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->call('theme:fix-header');
    }
}
