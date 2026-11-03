<?php

use App\Enums\ThemeLayoutLocale;
use App\Enums\ThemeLayoutRegion;
use App\Enums\ThemeLayoutStatus;
use App\Models\ThemeLayout;
use App\Services\ThemeLayoutRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the published layout for the requested language', function (): void {
    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::English->value,
        'published_html' => '<header id="english-header">English Header</header>',
        'status' => ThemeLayoutStatus::Published->value,
        'revision_number' => 2,
        'published_at' => now(),
    ]);

    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::Sinhala->value,
        'published_html' => '<header id="sinhala-header">සිංහල Header</header>',
        'status' => ThemeLayoutStatus::Published->value,
        'revision_number' => 2,
        'published_at' => now(),
    ]);

    $rendered = app(ThemeLayoutRenderer::class)->render(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Header,
        siteSettings: null,
        primaryMenu: null,
        socialLinks: [],
        languageOptions: [],
        currentLocale: ThemeLayoutLocale::Sinhala->value,
        siteName: 'AWCMS',
        siteTagline: 'Official website',
        logoUrl: null,
    );

    expect($rendered)
        ->not->toBeNull()
        ->and($rendered['html'] ?? '')
        ->toContain('id="sinhala-header"')
        ->toContain('සිංහල Header')
        ->not->toContain('english-header');
});

it('falls back to the published english layout when sinhala is unavailable', function (): void {
    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::English->value,
        'published_html' => '<header id="english-fallback">English Fallback</header>',
        'status' => ThemeLayoutStatus::Published->value,
        'revision_number' => 2,
        'published_at' => now(),
    ]);

    $rendered = app(ThemeLayoutRenderer::class)->render(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Header,
        siteSettings: null,
        primaryMenu: null,
        socialLinks: [],
        languageOptions: [],
        currentLocale: ThemeLayoutLocale::Sinhala->value,
        siteName: 'AWCMS',
        siteTagline: 'Official website',
        logoUrl: null,
    );

    expect($rendered)
        ->not->toBeNull()
        ->and($rendered['html'] ?? '')
        ->toContain('id="english-fallback"')
        ->toContain('English Fallback');
});

it('ignores a draft translation and uses the published english fallback', function (): void {
    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Footer->value,
        'locale' => ThemeLayoutLocale::English->value,
        'published_html' => '<footer id="published-english-footer">English Footer</footer>',
        'status' => ThemeLayoutStatus::Published->value,
        'revision_number' => 2,
        'published_at' => now(),
    ]);

    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Footer->value,
        'locale' => ThemeLayoutLocale::Tamil->value,
        'draft_html' => '<footer id="draft-tamil-footer">தமிழ் Footer</footer>',
        'status' => ThemeLayoutStatus::Draft->value,
        'revision_number' => 2,
    ]);

    $rendered = app(ThemeLayoutRenderer::class)->render(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Footer,
        siteSettings: null,
        primaryMenu: null,
        socialLinks: [],
        languageOptions: [],
        currentLocale: ThemeLayoutLocale::Tamil->value,
        siteName: 'AWCMS',
        siteTagline: 'Official website',
        logoUrl: null,
    );

    expect($rendered)
        ->not->toBeNull()
        ->and($rendered['html'] ?? '')
        ->toContain('id="published-english-footer"')
        ->not->toContain('draft-tamil-footer');
});

it('returns null when neither the requested nor english layout is published', function (): void {
    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::Tamil->value,
        'draft_html' => '<header id="draft-only">Draft Only</header>',
        'status' => ThemeLayoutStatus::Draft->value,
        'revision_number' => 2,
    ]);

    $rendered = app(ThemeLayoutRenderer::class)->render(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Header,
        siteSettings: null,
        primaryMenu: null,
        socialLinks: [],
        languageOptions: [],
        currentLocale: ThemeLayoutLocale::Tamil->value,
        siteName: 'AWCMS',
        siteTagline: 'Official website',
        logoUrl: null,
    );

    expect($rendered)->toBeNull();
});

it('renders the secure mobile menu toggle placeholder', function (): void {
    ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'locale' => ThemeLayoutLocale::English->value,
        'published_html' => '<header>[[mobile_menu_toggle]]<a href="[[site_url:/en/pages/about-us-2]]">About Us</a></header>',
        'status' => ThemeLayoutStatus::Published->value,
        'revision_number' => 2,
        'published_at' => now(),
    ]);

    $rendered = app(ThemeLayoutRenderer::class)->render(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Header,
        siteSettings: null,
        primaryMenu: null,
        socialLinks: [],
        languageOptions: [],
        currentLocale: ThemeLayoutLocale::English->value,
        siteName: 'AWCMS',
        siteTagline: 'Official website',
        logoUrl: null,
    );

    expect($rendered)
        ->not->toBeNull()
        ->and($rendered['html'] ?? '')
        ->toContain('id="mobileToggle"')
        ->toContain('aria-controls="mobileMenu"')
        ->toContain('data-mobile-toggle')
        ->toContain('cms-mobile-menu-toggle')
        ->not->toContain('[[mobile_menu_toggle]]')
        ->toContain('href="'.url('/en/pages/about-us-2').'"');
});
