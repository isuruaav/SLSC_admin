<?php

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationCategory;
use App\Services\OrganizationImportService;
use Database\Seeders\OrganizationCategoriesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(OrganizationCategoriesSeeder::class);
});

test('ssrf protection allows alt.army.lk and blocks unauthorized hosts', function (): void {
    $service = app(OrganizationImportService::class);

    expect($service->isSsrfSafe('https://alt.army.lk/signaller/organization'))->toBeTrue()
        ->and($service->isSsrfSafe('https://alt.army.lk/signaller/organization/ocso'))->toBeTrue()
        ->and($service->isSsrfSafe('https://www.army.lk/units'))->toBeTrue()
        ->and($service->isSsrfSafe('http://169.254.169.254/metadata'))->toBeFalse()
        ->and($service->isSsrfSafe('http://localhost:8000'))->toBeFalse()
        ->and($service->isSsrfSafe('http://127.0.0.1/admin'))->toBeFalse()
        ->and($service->isSsrfSafe('file:///etc/passwd'))->toBeFalse()
        ->and($service->isSsrfSafe('ftp://alt.army.lk'))->toBeFalse();
});

test('discoverUnits lists all 17 public reference units with structured metrics', function (): void {
    $service = app(OrganizationImportService::class);
    $discovered = $service->discoverUnits();

    expect(count($discovered))->toBe(17);

    $keys = array_column($discovered, 'key');
    expect($keys)->toContain(
        'ocso',
        'dit',
        '1-sig-bde',
        '2-sig-bde',
        'rc-slsc',
        '1-slsc',
        '3-slsc',
        '4-slsc',
        '5-slsc',
        '6-slsc-it',
        '7-slsc',
        '9-slsc',
        '10-slsc-ct',
        '11-slsc-it',
        '12-slsc-cs',
        '2v-slsc',
        'school-of-signals'
    );

    foreach ($discovered as $unit) {
        expect($unit)->toHaveKeys(['key', 'slug', 'name_en', 'category_slug', 'source_url', 'paragraph_count', 'table_count']);
        expect($unit['paragraph_count'])->toBeGreaterThanOrEqual(1);
    }
});

test('importing a unit creates draft record with translations and content blocks', function (): void {
    $service = app(OrganizationImportService::class);
    $result = $service->importUnit('school-of-signals', false);

    expect($result['success'])->toBeTrue();

    $org = Organization::where('slug', 'school-of-signals')->first();
    expect($org)->not->toBeNull();
    expect($org->status)->toBe(OrganizationStatus::Draft);
    expect($org->category->slug)->toBe('training-establishments');
    expect($org->source_url)->toBe('https://alt.army.lk/signaller/organization/school-of-signals');

    // Verify Multilingual Translations
    expect($org->titleForLocale('en'))->toBe('School of Signals');
    expect($org->titleForLocale('si'))->toBe('සංඥා පුහුණු පාසල');

    // Verify Content Blocks
    expect($org->contentBlocks()->count())->toBeGreaterThanOrEqual(3);
    $tableBlock = $org->contentBlocks()->where('block_type', 'table')->first();
    expect($tableBlock)->not->toBeNull();
    expect($tableBlock->structured_data['headers'])->toContain('Wing');
});

test('importing all 17 units succeeds idempotently in draft status', function (): void {
    $service = app(OrganizationImportService::class);
    $res = $service->importAll(true);

    expect($res['imported'])->toBe(17);
    expect($res['skipped'])->toBe(0);
    expect(Organization::count())->toBe(17);

    // Assert all imported units are strictly in Draft status
    $publishedCount = Organization::where('status', OrganizationStatus::Published->value)->count();
    expect($publishedCount)->toBe(0);
});

