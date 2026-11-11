<?php

use App\Models\FirstAppointmentHolder;
use Database\Seeders\FirstAppointmentHoldersSeeder;

beforeEach(function (): void {
    $this->seed(FirstAppointmentHoldersSeeder::class);
    config()->set('awcms.active_theme', 'slsc');
});

test('public first appointments page renders in English with 4-column grid', function (): void {
    $response = $this->get('/first-appoinment-holders');

    $response->assertOk();
    $response->assertSee('1st Appointments Holders');
    $response->assertSee('first-appointments-grid');
    $response->assertSee('Col CJ Abeyrathne');
    $response->assertSee('1st Regimental Commander');
    $response->assertSee('1988');
    $response->assertSee('Brig AMCW Senevirathna psc');
    $response->assertSee('1st Colonel Commandant');
    $response->assertSee('1997');
});

test('public first appointments page renders in Sinhala', function (): void {
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);

    $response = $this->get('/si/first-appoinment-holders');

    $response->assertOk();
    $response->assertSee('පළමු පත්වීම් හිමියන්');
    $response->assertSee('කර්නල් සීජේ අබේරත්න');
    $response->assertSee('1 වන රෙජිමේන්තු අණදෙන නිලධාරී');
});

test('si-first-appoinment-holders legacy route renders correctly', function (): void {
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);

    $response = $this->get('/si/si-first-appoinment-holders');

    $response->assertOk();
    $response->assertSee('පළමු පත්වීම් හිමියන්');
});

test('inactive appointment holder is not visible publicly', function (): void {
    $holder = FirstAppointmentHolder::first();
    $holder->update(['is_active' => false]);

    $response = $this->get('/first-appoinment-holders');
    $response->assertOk();
    $response->assertDontSee($holder->appointment_en);
});

