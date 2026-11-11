<?php

use App\Models\SchoolLeader;
use App\Models\SchoolLeadershipPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);

    $positions = [
        ['key' => 'colonel_commandant', 'name_en' => 'Colonel Commandant', 'name_si' => 'බලකාධිපති', 'sort_order' => 1],
        ['key' => 'chief_signal_officer', 'name_en' => 'Chief Signal Officer', 'name_si' => 'ප්‍රධාන සංඥා නිලධාරී', 'sort_order' => 2],
        ['key' => 'director_it', 'name_en' => 'Director IT', 'name_si' => 'අධ්‍යක්ෂ තොරතුරු තාක්ෂණ', 'sort_order' => 3],
        ['key' => 'commander_1_signal_brigade', 'name_en' => 'Commander 1 Signal Brigade', 'name_si' => '1 වන සංඥා බලසේනාධිපති', 'sort_order' => 4],
        ['key' => 'commander_2_signal_brigade', 'name_en' => 'Commander 2 Signal Brigade', 'name_si' => '2 වන සංඥා බලසේනාධිපති', 'sort_order' => 5],
        ['key' => 'regimental_centre_commandant', 'name_en' => 'Regimental Centre Commandant', 'name_si' => 'රෙජිමේන්තු මධ්‍යස්ථාන සේනාවිධායක', 'sort_order' => 6],
        ['key' => 'regimental_adjutant', 'name_en' => 'Regimental Adjutant', 'name_si' => 'විධායක අධිකාරි', 'sort_order' => 7],
        ['key' => 'regimental_sergeant_major', 'name_en' => 'Regimental Sergeant Major', 'name_si' => 'රෙජිමේන්තු සැරයන් මේජර්', 'sort_order' => 8],
    ];

    foreach ($positions as $p) {
        SchoolLeadershipPosition::query()->updateOrCreate(
            ['key' => $p['key']],
            [
                'name_en' => $p['name_en'],
                'name_si' => $p['name_si'],
                'rank_group' => 'commissioned',
                'sort_order' => $p['sort_order'],
                'is_active' => true,
            ]
        );
    }
});

it('renders the generic past-appointments page with default first appointment', function (): void {
    $this->get('/past-appointments')->assertOk()->assertSeeText('Regtl Comds & Col Comdts');
    $this->get('/si/past-appointments')->assertOk()->assertSeeText('රෙජිමේන්තු අණදෙන නිලධාරීන් හා බලකාධිපතිවරුන්');
});

it('renders dedicated page for each appointment with main title identical to Brigade Commanders page', function (string $url, string $expectedTitle): void {
    $response = $this->get($url)->assertOk();

    $response->assertSeeText($expectedTitle)
        ->assertSee('class="brigade-commanders-head"', false)
        ->assertSee('class="title-lg"', false);
})->with([
    ['/regt-comdt-col-comdt', 'Regtl Comds & Col Comdts'],
    ['/chief-signal-officers', 'Chief Signal Officers'],
    ['/directors-of-it', 'Directors of IT'],
    ['/1-bde-comds', '1 Bde Comds'],
    ['/2-bde-comds', '2 Bde Comds'],
    ['/centre-commandants', 'Cen Comdts'],
    ['/regimental-adjutants', 'Regimantal Adjutants'],
    ['/regimental-sergeant-major', 'Regimental Sergent Major'],
]);

it('strictly displays ONLY the appointment holders for the requested appointment', function (): void {
    $bdePosition = SchoolLeadershipPosition::query()->where('key', 'commander_1_signal_brigade')->firstOrFail();
    $ditPosition = SchoolLeadershipPosition::query()->where('key', 'director_it')->firstOrFail();
    $colComdtPosition = SchoolLeadershipPosition::query()->where('key', 'colonel_commandant')->firstOrFail();

    // 1 Bde Commander
    SchoolLeader::query()->create([
        'position_id' => $bdePosition->id,
        'role_key' => $bdePosition->key,
        'title_en' => $bdePosition->name_en,
        'name_en' => 'Brig CJ Abeyrathne',
        'rank' => 'brigadier',
        'start_date' => '1988-07-15',
        'end_date' => '1991-04-01',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    // Director IT
    SchoolLeader::query()->create([
        'position_id' => $ditPosition->id,
        'role_key' => $ditPosition->key,
        'title_en' => $ditPosition->name_en,
        'name_en' => 'Col MR Hameem',
        'rank' => 'colonel',
        'start_date' => '2026-01-02',
        'end_date' => null,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    // Col Commandant
    SchoolLeader::query()->create([
        'position_id' => $colComdtPosition->id,
        'role_key' => $colComdtPosition->key,
        'title_en' => $colComdtPosition->name_en,
        'name_en' => 'Maj Gen Bandaranayake',
        'rank' => 'major_general',
        'start_date' => '2025-12-15',
        'end_date' => null,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    // When visiting 1 Bde Comds: ONLY Brig CJ Abeyrathne appears, NOT Hameem or Bandaranayake!
    $bdeResponse = $this->get('/1-bde-comds')->assertOk();
    $bdeResponse->assertSeeText('Brig CJ Abeyrathne')
        ->assertSeeText('1988 - 07 - 15 to 1991 - 04 - 01')
        ->assertDontSeeText('Col MR Hameem')
        ->assertDontSeeText('Maj Gen Bandaranayake');

    // When visiting Directors of IT: ONLY Col MR Hameem appears, NOT Abeyrathne or Bandaranayake!
    $ditResponse = $this->get('/directors-of-it')->assertOk();
    $ditResponse->assertSeeText('Col MR Hameem')
        ->assertSeeText('Present')
        ->assertDontSeeText('Brig CJ Abeyrathne')
        ->assertDontSeeText('Maj Gen Bandaranayake');

    // When visiting Col Comdts: ONLY Maj Gen Bandaranayake appears!
    $colResponse = $this->get('/regt-comdt-col-comdt')->assertOk();
    $colResponse->assertSeeText('Maj Gen Bandaranayake')
        ->assertDontSeeText('Brig CJ Abeyrathne')
        ->assertDontSeeText('Col MR Hameem');
});

it('supports Sinhala language and displays localized appointment title and officer details', function (): void {
    $pos = SchoolLeadershipPosition::query()->where('key', 'director_it')->firstOrFail();

    SchoolLeader::query()->create([
        'position_id' => $pos->id,
        'role_key' => $pos->key,
        'title_en' => $pos->name_en,
        'title_si' => $pos->name_si,
        'name_en' => 'Col MR Hameem',
        'name_si' => 'කර්නල් එම්ආර් හමීම්',
        'rank' => 'colonel',
        'start_date' => '2026-01-02',
        'end_date' => null,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response = $this->get('/si/directors-of-it')->assertOk();

    $response->assertSeeText('තොරතුරු තාක්ෂණ අධ්‍යක්ෂකවරුන්')
        ->assertSeeText('කර්නල් එම්ආර් හමීම්')
        ->assertSeeText('අද දක්වා');
});

it('redirects alias and legacy URLs to standard routes', function (): void {
    $this->get('/cen-comdts')->assertRedirect('/centre-commandants');
    $this->get('/regimental-sergent-major')->assertRedirect('/regimental-sergeant-major');
    $this->get('/1_bde_comds')->assertRedirect('/1-bde-comds');
    $this->get('/2_bde_comds')->assertRedirect('/2-bde-comds');
    $this->get('/reg_adjutants')->assertRedirect('/regimental-adjutants');
    $this->get('/reg-adjutants')->assertRedirect('/regimental-adjutants');
    $this->get('/reg_sgt_major')->assertRedirect('/regimental-sergeant-major');
    $this->get('/reg-sgt-major')->assertRedirect('/regimental-sergeant-major');

    $this->get('/si/1_bde_comds')->assertRedirect('/si/1-bde-comds');
    $this->get('/si/2_bde_comds')->assertRedirect('/si/2-bde-comds');
    $this->get('/si/reg_adjutants')->assertRedirect('/si/regimental-adjutants');
    $this->get('/si/reg-adjutants')->assertRedirect('/si/regimental-adjutants');
    $this->get('/si/reg_sgt_major')->assertRedirect('/si/regimental-sergeant-major');
    $this->get('/si/reg-sgt-major')->assertRedirect('/si/regimental-sergeant-major');
});

it('does not display inactive appointment holders', function (): void {
    $pos = SchoolLeadershipPosition::query()->where('key', 'colonel_commandant')->firstOrFail();

    SchoolLeader::query()->create([
        'position_id' => $pos->id,
        'role_key' => $pos->key,
        'title_en' => $pos->name_en,
        'name_en' => 'Inactive Col Comdt',
        'rank' => 'major_general',
        'start_date' => '2010-01-01',
        'end_date' => '2012-01-01',
        'is_active' => false,
    ]);

    $this->get('/regt-comdt-col-comdt')
        ->assertOk()
        ->assertDontSeeText('Inactive Col Comdt');
});

it('displays additional tenure notes in English and Sinhala on the card', function (): void {
    $pos = SchoolLeadershipPosition::query()->where('key', 'commander_1_signal_brigade')->firstOrFail();

    SchoolLeader::query()->create([
        'position_id' => $pos->id,
        'role_key' => $pos->key,
        'title_en' => $pos->name_en,
        'name_en' => 'Col KA Gnanaweera',
        'name_si' => 'කර්නල් කේඒ ඥානවීර',
        'rank' => 'colonel',
        'start_date' => '1992-05-01',
        'end_date' => '1992-10-01',
        'secondary_tenure_en' => "Brig KA Gnanaweera\n1992 - 10 - 01 to 1993 - 06 - 27",
        'secondary_tenure_si' => "බ්‍රිගේඩියර් කේඒ ඥානවීර\n1992 - 10 - 01 සිට 1993 - 06 - 27 දක්වා",
        'sort_order' => 4,
        'is_active' => true,
    ]);

    $enResponse = $this->get('/1-bde-comds')->assertOk();
    $enResponse->assertSeeText('Col KA Gnanaweera')
        ->assertSeeText('Brig KA Gnanaweera')
        ->assertSeeText('1992 - 10 - 01 to 1993 - 06 - 27');

    $siResponse = $this->get('/si/1-bde-comds')->assertOk();
    $siResponse->assertSeeText('කර්නල් කේඒ ඥානවීර')
        ->assertSeeText('බ්‍රිගේඩියර් කේඒ ඥානවීර')
        ->assertSeeText('1992 - 10 - 01 සිට 1993 - 06 - 27 දක්වා');
});

it('allows admin to save and edit additional tenure notes in SchoolLeaderIndex form', function (): void {
    $pos = SchoolLeadershipPosition::query()->where('key', 'commander_1_signal_brigade')->firstOrFail();

    $user = \App\Models\User::factory()->create(['is_active' => true]);
    $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('school-leaders.view', 'web'));
    $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('school-leaders.update', 'web'));

    \Livewire\Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\SchoolLeaders\SchoolLeaderIndex::class)
        ->call('startCreate')
        ->set('roleKey', 'commander_1_signal_brigade')
        ->set('rank', 'colonel')
        ->set('nameEn', 'Col KA Gnanaweera')
        ->set('nameSi', 'කර්නල් කේඒ ඥානවීර')
        ->set('startDate', '1992-05-01')
        ->set('endDate', '1992-10-01')
        ->set('secondaryTenureEn', "Brig KA Gnanaweera\n1992 - 10 - 01 to 1993 - 06 - 27")
        ->set('secondaryTenureSi', "බ්‍රිගේඩියර් කේඒ ඥානවීර\n1992 - 10 - 01 සිට 1993 - 06 - 27 දක්වා")
        ->call('save')
        ->assertHasNoErrors();

    $leader = SchoolLeader::query()->where('name_en', 'Col KA Gnanaweera')->firstOrFail();
    expect($leader->secondary_tenure_en)->toBe("Brig KA Gnanaweera\n1992 - 10 - 01 to 1993 - 06 - 27")
        ->and($leader->secondary_tenure_si)->toBe("බ්‍රිගේඩියර් කේඒ ඥානවීර\n1992 - 10 - 01 සිට 1993 - 06 - 27 දක්වා");

    // Test editing loads the values into the form
    \Livewire\Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\SchoolLeaders\SchoolLeaderIndex::class)
        ->call('edit', $leader->id)
        ->assertSet('secondaryTenureEn', "Brig KA Gnanaweera\n1992 - 10 - 01 to 1993 - 06 - 27")
        ->assertSet('secondaryTenureSi', "බ්‍රිගේඩියර් කේඒ ඥානවීර\n1992 - 10 - 01 සිට 1993 - 06 - 27 දක්වා")
        ->set('secondaryTenureEn', 'Updated Note')
        ->call('save')
        ->assertHasNoErrors();

    expect($leader->refresh()->secondary_tenure_en)->toBe('Updated Note');
});
