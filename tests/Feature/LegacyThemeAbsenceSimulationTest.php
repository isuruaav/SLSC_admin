<?php

use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
});

it('verifies the legacy theme is physically absent during simulation', function (): void {
    expect(file_exists(base_path('themes/school-of-signals')))->toBeFalse('themes/school-of-signals should be unavailable during simulation')
        ->and(file_exists(public_path('themes/school-of-signals')))->toBeFalse('public/themes/school-of-signals should be unavailable during simulation');
});

it('renders English and Sinhala public homepages without legacy theme', function (): void {
    $en = $this->get('/')->assertOk();
    $en->assertSeeText('Sri Lanka Signal Corps')
        ->assertSee('data-open-search-modal', false)
        ->assertSee('/themes/slsc/assets/images/logo/signals-logo.png', false)
        ->assertDontSee('/themes/school-of-signals/', false);

    $si = $this->get('/si')->assertOk();
    $si->assertSeeText('ශ්‍රී ලංකා සංඥා බලකාය')
        ->assertSee('lang="si"', false)
        ->assertDontSee('/themes/school-of-signals/', false);
});

it('verifies Spotlight Search and live suggestions work without legacy theme', function (): void {
    $search = $this->get('/search?q=Signal')->assertOk();
    $search->assertSee('slscSearchModal', false)
        ->assertSee('data-search-modal', false)
        ->assertDontSee('/themes/school-of-signals/', false);

    $suggest = $this->getJson('/search/suggest?q=Signal')->assertOk();
    $suggest->assertJsonStructure(['results']);
});

it('renders leadership page', function (): void {
    $this->get('/school-leadership')->assertOk()->assertDontSee('/themes/school-of-signals/', false);
});

it('renders commandant profile page', function (): void {
    $position = \App\Models\SchoolLeadershipPosition::query()->firstOrCreate(['key' => 'commandant'], [
        'name_en' => 'The Commandant',
        'name_si' => 'සේනාවිධායක',
        'rank_group' => 'commissioned',
        'is_active' => true,
    ]);
    \App\Models\SchoolLeader::query()->create([
        'position_id' => $position->id,
        'role_key' => 'commandant',
        'title_en' => 'The Commandant',
        'name_en' => 'Commandant Officer',
        'name_si' => 'සේනාවිධායක',
        'rank' => 'colonel',
        'start_date' => '2025-01-01',
        'is_active' => true,
        'profile_enabled' => true,
        'profile_html_en' => '<p>Biography</p>',
    ]);
    $this->get('/school-leadership/commandant')->assertOk()->assertDontSee('/themes/school-of-signals/', false);
});

it('renders past commandants page', function (): void {
    $this->get(route('history.past-commandants'))->assertOk()->assertDontSee('/themes/school-of-signals/', false);
});

it('renders past sergeant majors page', function (): void {
    $this->get(route('history.past-sgt-major'))->assertOk()->assertDontSee('/themes/school-of-signals/', false);
});

it('renders news, courses and publications pages', function (): void {
    $this->get('/news')->assertOk()->assertDontSee('/themes/school-of-signals/', false);
    $this->get('/courses')->assertOk()->assertDontSee('/themes/school-of-signals/', false);
    $this->get('/publications')->assertOk()->assertDontSee('/themes/school-of-signals/', false);
});

it('renders friendly 404 error page safely without legacy theme', function (): void {
    $response = $this->get('/this-path-does-not-exist-simulation-test')->assertNotFound();
    $response->assertSee('We couldn’t find that page.')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertDontSee('/themes/school-of-signals/', false);
});

it('renders admin login and authenticated dashboard without legacy theme', function (): void {
    $this->get('/login')->assertOk()
        ->assertSee('form', false)
        ->assertDontSee('/themes/school-of-signals/', false);

    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrator');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertDontSee('/themes/school-of-signals/', false);
});

it('verifies all required SLSC public theme assets exist on disk', function (): void {
    $requiredAssets = [
        'public/themes/slsc/assets/css/base.css',
        'public/themes/slsc/assets/css/navbar.css',
        'public/themes/slsc/assets/css/news.css',
        'public/themes/slsc/assets/css/responsive.css',
        'public/themes/slsc/assets/css/style.css',
        'public/themes/slsc/assets/js/script.js',
        'public/themes/slsc/assets/js/search.js',
        'public/themes/slsc/assets/images/logo/signals-logo.png',
        'public/themes/slsc/assets/fonts/NotoSerifSinhala-Regular.woff2',
        'public/themes/slsc/assets/fonts/fontawesome-7/css/all.min.css',
    ];

    foreach ($requiredAssets as $asset) {
        expect(file_exists(base_path($asset)))->toBeTrue("Required asset missing: {$asset}");
    }
});
