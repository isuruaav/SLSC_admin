<?php

use App\Models\BrigadeCommander;
use Database\Seeders\BrigadeCommandersSeeder;

beforeEach(function (): void {
    $this->seed(BrigadeCommandersSeeder::class);
    config()->set('awcms.active_theme', 'slsc');
});

test('public brigade commanders page renders in English with 4-column grid', function (): void {
    $response = $this->get('/brigade-commanders');

    $response->assertOk();
    $response->assertSee('Brigade Commanders');
    $response->assertSee('brigade-commanders-grid');
    $response->assertSee('01. Brig CJ Abeyrathne');
    $response->assertSee('1988 - 07 - 15 to 1991 - 04 - 01');
    $response->assertSee('02. Brig FCJ de silva');
    $response->assertSee('04. Col KA Gnanaweera');
    $response->assertSee('Brig KA Gnanaweera');
    $response->assertSee('08. Col MA Wipulaguna');
});

test('public brigade commanders page renders in Sinhala', function (): void {
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);

    $response = $this->get('/si/brigade-commanders');

    $response->assertOk();
    $response->assertSee('බලසේනාධිපතිවරුන්');
    $response->assertSee('බ්‍රිගේඩියර් සීජේ අබේරත්න');
    $response->assertSee('කර්නල් කේඒ ඥානවීර');
});

test('si-brigade-commanders legacy route renders correctly', function (): void {
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);

    $response = $this->get('/si/si-brigade-commanders');

    $response->assertOk();
    $response->assertSee('බලසේනාධිපතිවරුන්');
});

test('bde-comds redirects to brigade-commanders', function (): void {
    $response = $this->get('/bde-comds');
    $response->assertRedirect('/brigade-commanders');
});

test('inactive brigade commander is not visible publicly', function (): void {
    $commander = BrigadeCommander::where('order_number', '02')->first();
    $commander->update(['is_active' => false]);

    $response = $this->get('/brigade-commanders');
    $response->assertOk();
    $response->assertDontSee('02. Brig FCJ de silva');
});
