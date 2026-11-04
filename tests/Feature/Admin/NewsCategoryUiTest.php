<?php

use App\Livewire\Admin\News\NewsCategoryIndex;
use App\Models\NewsCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(
        RolesAndPermissionsSeeder::class,
    );
});

test('site administrator can open news category management', function (): void {
    $administrator =
        User::factory()->create();

    $administrator->assignRole(
        'Site Administrator',
    );

    Livewire::actingAs(
        $administrator,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->assertSee(
            'News Categories',
        )
        ->assertSee(
            'New Category',
        );
});

test('publisher can open news category management', function (): void {
    $publisher =
        User::factory()->create();

    $publisher->assignRole(
        'Publisher',
    );

    Livewire::actingAs(
        $publisher,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->assertSee(
            'News Categories',
        );
});

test('content editor cannot open news category management', function (): void {
    $editor =
        User::factory()->create();

    $editor->assignRole(
        'Content Editor',
    );

    Livewire::actingAs(
        $editor,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->assertForbidden();
});

test('site administrator can create a news category through livewire', function (): void {
    $administrator =
        User::factory()->create();

    $administrator->assignRole(
        'Site Administrator',
    );

    Livewire::actingAs(
        $administrator,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->call(
            'openCreate',
        )
        ->assertSet(
            'showForm',
            true,
        )
        ->set(
            'name',
            'Army Events',
        )
        ->set(
            'description',
            'Official Army events and activities.',
        )
        ->set(
            'sortOrder',
            15,
        )
        ->set(
            'isActive',
            true,
        )
        ->call(
            'save',
        )
        ->assertHasNoErrors()
        ->assertSet(
            'showForm',
            false,
        )
        ->assertSet(
            'editingCategoryId',
            null,
        )
        ->assertSee(
            'News category was created successfully.',
        );

    $this->assertDatabaseHas(
        'news_categories',
        [
            'name' => 'Army Events',

            'slug' => 'army-events',

            'description' => 'Official Army events and activities.',

            'sort_order' => 15,

            'is_active' => true,

            'created_by' => $administrator->id,

            'updated_by' => $administrator->id,
        ],
    );
});

test('news category form validates required category name', function (): void {
    $administrator =
        User::factory()->create();

    $administrator->assignRole(
        'Site Administrator',
    );

    $initialCount = NewsCategory::query()->count();

    Livewire::actingAs(
        $administrator,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->call(
            'openCreate',
        )
        ->set(
            'name',
            '',
        )
        ->call(
            'save',
        )
        ->assertHasErrors([
            'name' => 'required',
        ]);

    expect(
        NewsCategory::query()->count(),
    )->toBe(
        $initialCount,
    );
});

test('administrator can open an existing category for editing', function (): void {
    $administrator =
        User::factory()->create();

    $administrator->assignRole(
        'Site Administrator',
    );

    $category =
        NewsCategory::factory()->create([
            'name' => 'Training News',

            'slug' => 'training-news',

            'description' => 'Training updates.',

            'sort_order' => 12,

            'is_active' => true,

            'created_by' => $administrator->id,

            'updated_by' => $administrator->id,
        ]);

    Livewire::actingAs(
        $administrator,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->call(
            'openEdit',
            $category->id,
        )
        ->assertSet(
            'showForm',
            true,
        )
        ->assertSet(
            'editingCategoryId',
            $category->id,
        )
        ->assertSet(
            'name',
            'Training News',
        )
        ->assertSet(
            'description',
            'Training updates.',
        )
        ->assertSet(
            'sortOrder',
            12,
        )
        ->assertSet(
            'isActive',
            true,
        );
});

test('publisher can update a news category through livewire', function (): void {
    $administrator =
        User::factory()->create();

    $administrator->assignRole(
        'Site Administrator',
    );

    $publisher =
        User::factory()->create();

    $publisher->assignRole(
        'Publisher',
    );

    $category =
        NewsCategory::factory()->create([
            'name' => 'Old Category',

            'slug' => 'old-category',

            'description' => 'Old description.',

            'sort_order' => 5,

            'is_active' => true,

            'created_by' => $administrator->id,

            'updated_by' => $administrator->id,
        ]);

    Livewire::actingAs(
        $publisher,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->call(
            'openEdit',
            $category->id,
        )
        ->set(
            'name',
            'Operations',
        )
        ->set(
            'description',
            'Operational news and updates.',
        )
        ->set(
            'sortOrder',
            25,
        )
        ->set(
            'isActive',
            true,
        )
        ->call(
            'save',
        )
        ->assertHasNoErrors()
        ->assertSet(
            'showForm',
            false,
        )
        ->assertSee(
            'News category was updated successfully.',
        );

    $this->assertDatabaseHas(
        'news_categories',
        [
            'id' => $category->id,

            'name' => 'Operations',

            'slug' => 'operations',

            'description' => 'Operational news and updates.',

            'sort_order' => 25,

            'updated_by' => $publisher->id,
        ],
    );
});

test('administrator can deactivate and reactivate a category', function (): void {
    $administrator =
        User::factory()->create();

    $administrator->assignRole(
        'Site Administrator',
    );

    $category =
        NewsCategory::factory()->create([
            'name' => 'Sports',

            'slug' => 'sports',

            'is_active' => true,

            'created_by' => $administrator->id,

            'updated_by' => $administrator->id,
        ]);

    Livewire::actingAs(
        $administrator,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->call(
            'toggleActive',
            $category->id,
        )
        ->assertSee(
            'News category was deactivated successfully.',
        );

    $category->refresh();

    expect(
        $category->is_active,
    )->toBeFalse();

    Livewire::actingAs(
        $administrator,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->call(
            'toggleActive',
            $category->id,
        )
        ->assertSee(
            'News category was activated successfully.',
        );

    $category->refresh();

    expect(
        $category->is_active,
    )->toBeTrue();
});

test('category search filters the visible category list', function (): void {
    $administrator =
        User::factory()->create();

    $administrator->assignRole(
        'Site Administrator',
    );

    NewsCategory::factory()->create([
        'name' => 'Training News',

        'slug' => 'training-news',

        'description' => 'Training related content.',
    ]);

    NewsCategory::factory()->create([
        'name' => 'Sports News',

        'slug' => 'sports-news',

        'description' => 'Army sports activities.',
    ]);

    Livewire::actingAs(
        $administrator,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->set(
            'search',
            'Training',
        )
        ->assertSee(
            'Training News',
        )
        ->assertDontSee(
            'Sports News',
        );
});

test('clear search resets category search field', function (): void {
    $administrator =
        User::factory()->create();

    $administrator->assignRole(
        'Site Administrator',
    );

    Livewire::actingAs(
        $administrator,
    )
        ->test(
            NewsCategoryIndex::class,
        )
        ->set(
            'search',
            'Training',
        )
        ->call(
            'clearSearch',
        )
        ->assertSet(
            'search',
            '',
        );
});
