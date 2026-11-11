<?php

namespace Database\Seeders;

use App\Services\OrganizationImportService;
use Illuminate\Database\Seeder;

final class OrganizationLegacyImportSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(OrganizationImportService::class);
        $result = $service->importAll(true);

        $this->command->info("Organization Legacy Import: Imported {$result['imported']} units, skipped {$result['skipped']}.");
    }
}

