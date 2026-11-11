<?php

use App\Enums\OrganizationStatus;
use App\Livewire\Admin\Organizations\OrganizationCategoryIndex;
use App\Livewire\Admin\Organizations\OrganizationEditor;
use App\Livewire\Admin\Organizations\OrganizationImportIndex;
use App\Livewire\Admin\Organizations\OrganizationIndex;
use App\Models\Organization;
use App\Models\OrganizationCategory;
use App\Models\User;
use Database\Seeders\OrganizationCategoriesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(OrganizationCategoriesSeeder::class);
});

test('unauthenticated users are redirected from admin organization pages', function (): void {
    $this->get(route('admin.organizations.index'))->assertRedirect('/login');
    $this->get(route('admin.organizations.create'))->assertRedirect('/login');
    $this->get(route('admin.organizations.categories.index'))->assertRedirect('/login');
    $this->get(route('admin.organizations.import.index'))->assertRedirect('/login');
});

test('users without permissions are forbidden', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('admin.organizations.index'))
        ->assertForbidden();
});

test('authorized users can view organizations index and toggle publish status', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $org = Organization::create([
        'slug' => 'test-regiment',
        'status' => OrganizationStatus::Draft,
        'sort_order' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.organizations.index'))
        ->assertOk()
        ->assertSee('Organization Management')
        ->assertSee('All Organizations');

    $this->actingAs($admin)
        ->get(route('admin.organizations.edit', $org->id))
        ->assertOk()
        ->assertSee('Organization Management')
        ->assertSee('All Organizations');

    Livewire::actingAs($admin)
        ->test(OrganizationIndex::class)
        ->call('togglePublish', $org->id);

    expect($org->fresh()->status)->toBe(OrganizationStatus::Published);
});

test('organization editor can create unit with content blocks and dynamic tables', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $category = OrganizationCategory::first();

    Livewire::actingAs($admin)
        ->test(OrganizationEditor::class)
        ->set('translations.en.title', '1st Communication Unit')
        ->set('slug', '1st-comm-unit')
        ->set('shortName', '1 CU')
        ->set('categoryId', $category->id)
        ->set('location', 'Panagoda')
        ->call('addBlock', 'table')
        ->call('addTableRow', 1)
        ->call('save', false);

    $created = Organization::where('slug', '1st-comm-unit')->first();
    expect($created)->not->toBeNull();
    expect($created->titleForLocale('en'))->toBe('1st Communication Unit');
    expect($created->status)->toBe(OrganizationStatus::Draft);
    expect($created->contentBlocks()->where('block_type', 'table')->exists())->toBeTrue();
});

test('organization index allows inline quick edit of short name', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $org = Organization::create([
        'slug' => 'test-short-name-org',
        'short_name' => 'OLD',
        'status' => OrganizationStatus::Draft,
        'sort_order' => 1,
    ]);

    Livewire::actingAs($admin)
        ->test(OrganizationIndex::class)
        ->call('startEditShortName', $org->id, 'OLD')
        ->set('editingShortNameValue', 'OCSO-NEW')
        ->call('saveShortName', $org->id);

    expect($org->fresh()->short_name)->toBe('OCSO-NEW');
});

test('organization category index can create and update categories and reorder via drag and drop', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $cat1 = OrganizationCategory::create([
        'name_en' => 'Category One',
        'slug' => 'cat-one',
        'sort_order' => 1,
    ]);
    $cat2 = OrganizationCategory::create([
        'name_en' => 'Category Two',
        'slug' => 'cat-two',
        'sort_order' => 2,
    ]);

    Livewire::actingAs($admin)
        ->test(OrganizationCategoryIndex::class)
        ->call('reorderCategories', [$cat2->id, $cat1->id]);

    expect($cat2->fresh()->sort_order)->toBe(1);
    expect($cat1->fresh()->sort_order)->toBe(2);

    Livewire::actingAs($admin)
        ->test(OrganizationCategoryIndex::class)
        ->call('startCreate')
        ->set('name_en', 'Cyber Formations')
        ->set('slug', 'cyber-formations')
        ->set('sort_order', 10)
        ->call('save');

    expect(OrganizationCategory::where('slug', 'cyber-formations')->exists())->toBeTrue();
});

test('organization import index can trigger batch import', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    Livewire::actingAs($admin)
        ->test(OrganizationImportIndex::class)
        ->set('selectedKeys', ['ocso', 'dit'])
        ->call('importSelected');

    expect(Organization::where('slug', 'chief-signal-officer')->exists())->toBeTrue();
    expect(Organization::where('slug', 'directorate-of-it')->exists())->toBeTrue();
});

