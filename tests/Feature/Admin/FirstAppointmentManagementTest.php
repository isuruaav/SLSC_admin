<?php

use App\Models\FirstAppointmentHolder;
use App\Models\User;
use Database\Seeders\FirstAppointmentHoldersSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use App\Livewire\Admin\FirstAppointments\FirstAppointmentIndex;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(FirstAppointmentHoldersSeeder::class);
    config()->set('awcms.active_theme', 'slsc');
});

test('guest cannot access admin first appointments index', function (): void {
    $this->get(route('admin.first-appointments.index'))
        ->assertRedirect(route('login'));
});

test('authorized admin can view first appointments index', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $this->actingAs($admin)
        ->get(route('admin.first-appointments.index'))
        ->assertOk()
        ->assertSee('1st Appointments Holders')
        ->assertSee('Col CJ Abeyrathne')
        ->assertSee('Brig AMCW Senevirathna psc');
});

test('admin can create a new first appointment holder with bilingual content', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    Livewire::actingAs($admin)
        ->test(FirstAppointmentIndex::class)
        ->call('startCreate')
        ->set('appointmentEn', '1st Chief Instructor')
        ->set('appointmentSi', '1 වන ප්‍රධාන උපදේශක')
        ->set('nameEn', 'Col A B Silva')
        ->set('nameSi', 'කර්නල් ඒ බී සිල්වා')
        ->set('periodEn', '1995')
        ->set('periodSi', '1995')
        ->set('sortOrder', 10)
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $created = FirstAppointmentHolder::where('appointment_en', '1st Chief Instructor')->first();
    expect($created)->not->toBeNull();
    expect($created->name_en)->toBe('Col A B Silva');
    expect($created->name_si)->toBe('කර්නල් ඒ බී සිල්වා');
    expect($created->period_en)->toBe('1995');
    expect($created->is_active)->toBeTrue();
});

test('admin can edit an existing appointment holder', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $holder = FirstAppointmentHolder::first();

    Livewire::actingAs($admin)
        ->test(FirstAppointmentIndex::class)
        ->call('edit', $holder->id)
        ->set('nameEn', 'Col CJ Abeyrathne USP')
        ->call('save')
        ->assertHasNoErrors();

    expect($holder->fresh()->name_en)->toBe('Col CJ Abeyrathne USP');
});

test('admin can toggle active status', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $holder = FirstAppointmentHolder::first();
    expect($holder->is_active)->toBeTrue();

    Livewire::actingAs($admin)
        ->test(FirstAppointmentIndex::class)
        ->call('toggleActive', $holder->id);

    expect($holder->fresh()->is_active)->toBeFalse();
});

test('admin can reorder appointment holders via drag and drop', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $h1 = FirstAppointmentHolder::find(1);
    $h2 = FirstAppointmentHolder::find(2);

    Livewire::actingAs($admin)
        ->test(FirstAppointmentIndex::class)
        ->call('reorderHolders', [$h2->id, $h1->id]);

    expect($h2->fresh()->sort_order)->toBe(1);
    expect($h1->fresh()->sort_order)->toBe(2);
});

test('admin can delete an appointment holder', function (): void {
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole('Super Administrator');

    $holder = FirstAppointmentHolder::create([
        'appointment_en' => 'To Be Deleted',
        'name_en' => 'Officer Test',
        'sort_order' => 99,
        'is_active' => true,
    ]);

    Livewire::actingAs($admin)
        ->test(FirstAppointmentIndex::class)
        ->call('delete', $holder->id);

    expect(FirstAppointmentHolder::find($holder->id))->toBeNull();
});

