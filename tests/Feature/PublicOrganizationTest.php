<?php

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationCategory;
use App\Models\OrganizationContentBlock;
use App\Models\OrganizationTranslation;
use App\Models\User;
use Database\Seeders\OrganizationCategoriesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(OrganizationCategoriesSeeder::class);
});

test('public organization directory returns 200 and lists published units', function (): void {
    $category = OrganizationCategory::first();

    $org = Organization::create([
        'category_id' => $category->id,
        'slug' => 'test-regiment',
        'status' => OrganizationStatus::Published,
        'published_at' => now(),
        'sort_order' => 1,
    ]);

    OrganizationTranslation::create([
        'organization_id' => $org->id,
        'locale' => 'en',
        'title' => 'Test Signals Regiment',
        'summary' => 'Tactical operational communications unit.',
    ]);

    $response = $this->get(route('organization.index'));
    $response->assertOk();
    $response->assertSee('Test Signals Regiment');
});

test('public organization directory supports sinhala localized route', function (): void {
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);

    $responseSi = $this->get(route('organization.index.localized', ['locale' => 'si']));
    $responseSi->assertOk();
    $responseSi->assertSee('සංවිධාන ව්‍යුහය');
});

test('draft organization returns 404 for guests but is viewable by authorized admins', function (): void {
    $org = Organization::create([
        'slug' => 'confidential-formation',
        'status' => OrganizationStatus::Draft,
        'sort_order' => 1,
    ]);

    OrganizationTranslation::create([
        'organization_id' => $org->id,
        'locale' => 'en',
        'title' => 'Confidential Formation',
    ]);

    // Guest gets 404
    $this->get(route('organization.show', ['slug' => 'confidential-formation']))
        ->assertNotFound();

    // Admin with permission can preview
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $this->actingAs($admin)
        ->get(route('organization.show', ['slug' => 'confidential-formation']))
        ->assertOk()
        ->assertSee('Confidential Formation')
        ->assertSee('Draft Preview');
});

test('published organization detail page renders content blocks and tables', function (): void {
    $category = OrganizationCategory::where('slug', 'regiments')->first();

    $org = Organization::create([
        'category_id' => $category->id,
        'slug' => '1st-regiment-slsc',
        'short_name' => '1 SLSC',
        'status' => OrganizationStatus::Published,
        'published_at' => now(),
        'sort_order' => 1,
        'location' => 'Mullaitivu',
    ]);

    OrganizationTranslation::create([
        'organization_id' => $org->id,
        'locale' => 'en',
        'title' => '1st Regiment Sri Lanka Signal Corps',
        'subtitle' => 'The First and Foremost',
        'summary' => 'The premier combat communication regiment.',
    ]);

    OrganizationContentBlock::create([
        'organization_id' => $org->id,
        'locale' => 'en',
        'block_type' => 'table',
        'title' => 'Past Commanding Officers',
        'structured_data' => [
            'headers' => ['No', 'Rank & Name', 'Tenure'],
            'rows' => [
                ['01', 'Lt Col D.V. Brohier', '1958 - 1962'],
            ],
        ],
        'sort_order' => 1,
    ]);

    $response = $this->get(route('organization.show', ['slug' => '1st-regiment-slsc']));
    $response->assertOk();
    $response->assertSee('1st Regiment Sri Lanka Signal Corps');
    $response->assertSee('The First and Foremost');
    $response->assertSee('Lt Col D.V. Brohier');
    $response->assertSee('Mullaitivu');
});
