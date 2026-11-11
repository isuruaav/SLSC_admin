<?php

use App\Models\BrigadeCommander;
use App\Models\User;
use Database\Seeders\BrigadeCommandersSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use App\Livewire\Admin\BrigadeCommanders\BrigadeCommanderIndex;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BrigadeCommandersSeeder::class);
    config()->set('awcms.active_theme', 'slsc');
});

test('guest cannot access admin brigade commanders index', function (): void {
    $this->get(route('admin.brigade-commanders.index'))
        ->assertRedirect(route('login'));
});

test('authorized admin can view brigade commanders index', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $this->actingAs($admin)
        ->get(route('admin.brigade-commanders.index'))
        ->assertOk()
        ->assertSee('Brigade Commanders')
        ->assertSee('Brig CJ Abeyrathne')
        ->assertSee('Brig FCJ de silva');
});

test('admin can create a new brigade commander with bilingual content and time period', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    Livewire::actingAs($admin)
        ->test(BrigadeCommanderIndex::class)
        ->call('startCreate')
        ->set('orderNumber', '09')
        ->set('nameEn', 'Col P S R Bandara')
        ->set('nameSi', 'කර්නල් පී එස් ආර් බණ්ඩාර')
        ->set('startDate', '1997-01-01')
        ->set('endDate', '1998-05-15')
        ->set('periodEn', '1997 - 01 - 01 to 1998 - 05 - 15')
        ->set('periodSi', '1997 - 01 - 01 සිට 1998 - 05 - 15 දක්වා')
        ->set('sortOrder', 9)
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $created = BrigadeCommander::where('order_number', '09')->first();
    expect($created)->not->toBeNull();
    expect($created->name_en)->toBe('Col P S R Bandara');
    expect($created->name_si)->toBe('කර්නල් පී එස් ආර් බණ්ඩාර');
    expect($created->period_en)->toBe('1997 - 01 - 01 to 1998 - 05 - 15');
    expect($created->is_active)->toBeTrue();
});

test('admin can edit an existing brigade commander and tenure', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $commander = BrigadeCommander::first();

    Livewire::actingAs($admin)
        ->test(BrigadeCommanderIndex::class)
        ->call('edit', $commander->id)
        ->set('nameEn', 'Maj Gen CJ Abeyrathne')
        ->call('save')
        ->assertHasNoErrors();

    expect($commander->fresh()->name_en)->toBe('Maj Gen CJ Abeyrathne');
});

test('admin can toggle active status of a brigade commander', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $commander = BrigadeCommander::first();
    expect($commander->is_active)->toBeTrue();

    Livewire::actingAs($admin)
        ->test(BrigadeCommanderIndex::class)
        ->call('toggleActive', $commander->id);

    expect($commander->fresh()->is_active)->toBeFalse();
});

test('admin can reorder brigade commanders via drag and drop', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $c1 = BrigadeCommander::find(1);
    $c2 = BrigadeCommander::find(2);

    Livewire::actingAs($admin)
        ->test(BrigadeCommanderIndex::class)
        ->call('reorderCommanders', [$c2->id, $c1->id]);

    expect($c2->fresh()->sort_order)->toBe(1);
    expect($c1->fresh()->sort_order)->toBe(2);
});

test('admin can delete a brigade commander', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $commander = BrigadeCommander::create([
        'order_number' => '99',
        'name_en' => 'To Be Removed',
        'sort_order' => 99,
        'is_active' => true,
    ]);

    Livewire::actingAs($admin)
        ->test(BrigadeCommanderIndex::class)
        ->call('delete', $commander->id);

    expect(BrigadeCommander::find($commander->id))->toBeNull();
});

