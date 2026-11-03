<?php

use App\Enums\ThemeLayoutLocale;
use App\Enums\ThemeLayoutRegion;
use App\Enums\ThemeLayoutStatus;
use App\Models\SiteSetting;
use App\Models\ThemeLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');

    SiteSetting::query()->create([
        'site_name' => 'Sri Lanka School of Signals',
        'site_tagline' => 'Technological Sound',
        'theme_family' => 'training-school',
        'primary_color' => '#0f172a',
        'accent_color' => '#16a34a',
    ]);
});

it('keeps the original theme header and footer without published layouts', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('class="topbar"', false)
        ->assertSee('class="site-header"', false)
        ->assertSee('class="footer"', false);
});

it('ignores draft layouts on the public website', function (): void {
    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::English->value,
        'draft_html' => '<header>Unpublished Managed Header</header>',
        'status' => ThemeLayoutStatus::Draft->value,
        'revision_number' => 1,
    ]);

    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Footer->value,
        'locale' => ThemeLayoutLocale::English->value,
        'draft_html' => '<footer>Unpublished Managed Footer</footer>',
        'status' => ThemeLayoutStatus::Draft->value,
        'revision_number' => 1,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('class="topbar"', false)
        ->assertSee('class="footer"', false)
        ->assertDontSee('Unpublished Managed Header')
        ->assertDontSee('Unpublished Managed Footer');
});

it('renders published managed header footer and sanitized css', function (): void {
    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::English->value,
        'published_html' => '<header class="managed-school-header">Managed English Header [[site_name]]</header>',
        'published_css' => '.managed-school-header { display: flex; }',
        'status' => ThemeLayoutStatus::Published->value,
        'revision_number' => 2,
        'published_at' => now(),
    ]);

    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Footer->value,
        'locale' => ThemeLayoutLocale::English->value,
        'published_html' => '<footer class="managed-school-footer">Managed English Footer [[copyright_year]]</footer>',
        'published_css' => '.managed-school-footer { display: block; }',
        'status' => ThemeLayoutStatus::Published->value,
        'revision_number' => 2,
        'published_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Managed English Header')
        ->assertSee('Sri Lanka School of Signals')
        ->assertSee('Managed English Footer')
        ->assertSee('data-awcms-theme-layout="header"', false)
        ->assertSee('data-awcms-theme-layout="footer"', false)
        ->assertSee('.managed-school-header { display: flex; }', false)
        ->assertSee('.managed-school-footer { display: block; }', false)
        ->assertDontSee('class="topbar"', false)
        ->assertDontSee('class="footer"', false);
});
