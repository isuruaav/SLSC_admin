<?php

use App\Enums\ThemeLayoutLocale;
use App\Enums\ThemeLayoutRegion;
use App\Enums\ThemeLayoutStatus;
use App\Livewire\Admin\ThemeLayouts\ThemeLayoutIndex;
use App\Models\ThemeLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::findOrCreate('admin.access', 'web');
    Permission::findOrCreate('theme-layouts.manage', 'web');

    config()->set('awcms.active_theme', 'slsc');
});

function themeLayoutAdminUser(bool $mayManageLayouts = true): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $permissions = ['admin.access'];

    if ($mayManageLayouts) {
        $permissions[] = 'theme-layouts.manage';
    }

    $user->givePermissionTo($permissions);

    return $user;
}

it('allows an authorized administrator to open the multilingual editor', function (): void {
    $this->actingAs(themeLayoutAdminUser())
        ->get(route('admin.theme-layouts.index'))
        ->assertOk()
        ->assertSee('Header &amp; Footer', false)
        ->assertSee('slsc')
        ->assertSee('English')
        ->assertSee('සිංහල')
        ->assertDontSee('தமிழ்');
});

it('forbids administrators without the dedicated layout permission', function (): void {
    $this->actingAs(themeLayoutAdminUser(false))
        ->get(route('admin.theme-layouts.index'))
        ->assertForbidden();
});

it('keeps language editor content independent when switching tabs', function (): void {
    $this->actingAs(themeLayoutAdminUser());

    Livewire::test(ThemeLayoutIndex::class)
        ->assertSet('themeSlug', 'slsc')
        ->assertSet('region', ThemeLayoutRegion::Header->value)
        ->assertSet('locale', ThemeLayoutLocale::English->value)
        ->set(
            'layoutHtml',
            '<header>English [[site_name]] [[primary_menu]]</header>',
        )
        ->set(
            'layoutCss',
            '.school-header { color: #ffffff; }',
        )
        ->call('saveDraft')
        ->assertHasNoErrors()
        ->call('selectLocale', ThemeLayoutLocale::Sinhala->value)
        ->assertSet('locale', ThemeLayoutLocale::Sinhala->value)
        ->assertSet('layoutHtml', '')
        ->set(
            'layoutHtml',
            '<header>සිංහල [[site_name]] [[primary_menu]]</header>',
        )
        ->set(
            'layoutCss',
            '.school-header { color: #f8fafc; }',
        )
        ->call('saveDraft')
        ->assertHasNoErrors()
        ->call('selectLocale', ThemeLayoutLocale::English->value)
        ->assertSet('locale', ThemeLayoutLocale::English->value)
        ->assertSet(
            'layoutHtml',
            '<header>English [[site_name]] [[primary_menu]]</header>',
        );

    expect(
        ThemeLayout::query()
            ->where('theme_slug', 'slsc')
            ->where('region', ThemeLayoutRegion::Header->value)
            ->count(),
    )->toBe(2);

    $this->assertDatabaseHas('theme_layouts', [
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::English->value,
    ]);

    $this->assertDatabaseHas('theme_layouts', [
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::Sinhala->value,
    ]);
});

it('saves and publishes only the selected language layout', function (): void {
    $this->actingAs(themeLayoutAdminUser());
    \App\Models\SiteSetting::current()->update(['enabled_locales' => ['en', 'si', 'ta']]);

    Livewire::test(ThemeLayoutIndex::class)
        ->call('selectLocale', ThemeLayoutLocale::Tamil->value)
        ->assertSet('locale', ThemeLayoutLocale::Tamil->value)
        ->set(
            'layoutHtml',
            '<header class="school-header" onclick="alert(1)">தமிழ் [[site_name]] [[primary_menu]]</header>',
        )
        ->set(
            'layoutCss',
            '.school-header { background: #0f172a; }',
        )
        ->call('saveDraft')
        ->assertHasNoErrors()
        ->call('publish')
        ->assertHasNoErrors();

    $layout = ThemeLayout::query()
        ->where('theme_slug', 'slsc')
        ->where('region', ThemeLayoutRegion::Header->value)
        ->where('locale', ThemeLayoutLocale::Tamil->value)
        ->firstOrFail();

    expect($layout->status)->toBe(ThemeLayoutStatus::Published)
        ->and($layout->locale)->toBe(ThemeLayoutLocale::Tamil)
        ->and($layout->draft_html)->toContain('தமிழ்')
        ->and($layout->draft_html)->toContain('[[primary_menu]]')
        ->and($layout->draft_html)->not->toContain('onclick')
        ->and($layout->published_html)->toBe($layout->draft_html)
        ->and($layout->revision_number)->toBe(2);

    $this->assertDatabaseMissing('theme_layouts', [
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::English->value,
    ]);
});

it('saves reloads and publishes icon CSS without losing its escapes', function (string $region, string $locale): void {
    $this->actingAs(themeLayoutAdminUser());

    $css = <<<'CSS'
.managed-school-footer-contact .cms-contact-address::before { content: "\f3c5"; }
.managed-school-footer-contact .cms-contact-phone::before { content: "\f095"; }
.managed-school-footer-contact .cms-contact-email::before { content: "\f0e0"; }
CSS;

    Livewire::test(ThemeLayoutIndex::class)
        ->call('selectRegion', $region)
        ->call('selectLocale', $locale)
        ->set('layoutHtml', '<'.$region.' class="managed-school-footer">[[site_name]]</'.$region.'>')
        ->set('layoutCss', $css)
        ->call('saveDraft')
        ->assertHasNoErrors();

    Livewire::test(ThemeLayoutIndex::class)
        ->call('selectRegion', $region)
        ->call('selectLocale', $locale)
        ->assertSet('layoutCss', $css)
        ->call('publish')
        ->assertHasNoErrors();

    $layout = ThemeLayout::query()->where('region', $region)->where('locale', $locale)->firstOrFail();

    expect($layout->draft_css)->toBe($css)
        ->and($layout->published_css)->toBe($css)
        ->and($layout->revisions()->firstOrFail()->content_css)->toBe($css);

    $this->get($locale === 'en' ? route('home') : route('home.localized', ['locale' => $locale]))
        ->assertOk()
        ->assertSee('data-awcms-theme-layout="'.$region.'"', false)
        ->assertSee($css, false);
})->with([
    'English header' => ['header', 'en'],
    'Sinhala header' => ['header', 'si'],
    'English footer' => ['footer', 'en'],
    'Sinhala footer' => ['footer', 'si'],
]);
