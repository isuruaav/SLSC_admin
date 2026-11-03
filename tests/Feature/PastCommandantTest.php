<?php

use App\Models\PastCommandant;
use App\Models\SchoolLeader;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);
});

it('renders the present commandant above past commandants in both public languages', function (): void {
    SchoolLeader::query()->create([
        'role_key' => SchoolLeader::ROLE_COMMANDANT,
        'title_en' => 'The Commandant',
        'title_si' => 'සේනාවිධායක',
        'name_en' => 'Major Current Commander',
        'name_si' => 'වර්තමාන සේනාවිධායක',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    PastCommandant::query()->create([
        'name_en' => 'Major AA Perera SLSC',
        'name_si' => 'මේජර් ඒ ඒ පෙරේරා SLSC',
        'from_date' => '1993-12-27',
        'to_date' => '1994-09-14',
    ]);

    $this->get('/en/history/past-commandants')
        ->assertOk()
        ->assertSeeText('Present Commandant')
        ->assertSeeText('Major Current Commander')
        ->assertSeeText('Major AA Perera SLSC')
        ->assertSeeText('27.12.1993')
        ->assertSeeText('14.09.1994');

    $this->get('/si/history/past-commandants')
        ->assertOk()
        ->assertSeeText('වර්තමාන සේනාවිධායක')
        ->assertSeeText('මේජර් ඒ ඒ පෙරේරා SLSC');
});
