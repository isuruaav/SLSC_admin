<?php

use App\Enums\ThemeLayoutLocale;
use App\Enums\ThemeLayoutRegion;
use App\Enums\ThemeLayoutStatus;
use App\Models\ThemeLayout;
use App\Models\User;
use App\Services\ThemeLayoutService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::findOrCreate('theme-layouts.manage', 'web');
});

it('saves a sanitized localized draft with a revision and audit entry', function (): void {
    $actor = User::factory()->create();
    $actor->givePermissionTo('theme-layouts.manage');

    $layout = app(ThemeLayoutService::class)->saveDraft(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Header,
        locale: ThemeLayoutLocale::English,
        actor: $actor,
        html: '<header class="flex" onclick="alert(1)">[[primary_menu]]</header>',
        css: '.site-header { display: flex; }',
    );

    expect($layout->draft_html)
        ->toContain('[[primary_menu]]')
        ->not->toContain('onclick')
        ->and($layout->locale)->toBe(ThemeLayoutLocale::English)
        ->and($layout->revision_number)->toBe(1)
        ->and($layout->revisions()->count())->toBe(1);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'theme-layouts.draft-saved',
        'actor_id' => $actor->id,
        'subject_id' => $layout->id,
    ]);
});

it('stores each header language independently', function (): void {
    $actor = User::factory()->create();
    $actor->givePermissionTo('theme-layouts.manage');

    $service = app(ThemeLayoutService::class);

    $english = $service->saveDraft(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Header,
        locale: ThemeLayoutLocale::English,
        actor: $actor,
        html: '<header>English [[primary_menu]]</header>',
        css: '.site-header { color: #ffffff; }',
    );

    $sinhala = $service->saveDraft(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Header,
        locale: ThemeLayoutLocale::Sinhala,
        actor: $actor,
        html: '<header>සිංහල [[primary_menu]]</header>',
        css: '.site-header { color: #f8fafc; }',
    );

    $tamil = $service->saveDraft(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Header,
        locale: ThemeLayoutLocale::Tamil,
        actor: $actor,
        html: '<header>தமிழ் [[primary_menu]]</header>',
        css: '.site-header { color: #f1f5f9; }',
    );

    expect($english->id)
        ->not->toBe($sinhala->id)
        ->not->toBe($tamil->id)
        ->and($sinhala->id)->not->toBe($tamil->id)
        ->and($english->locale)->toBe(ThemeLayoutLocale::English)
        ->and($sinhala->locale)->toBe(ThemeLayoutLocale::Sinhala)
        ->and($tamil->locale)->toBe(ThemeLayoutLocale::Tamil)
        ->and($english->draft_html)->toContain('English')
        ->and($sinhala->draft_html)->toContain('සිංහල')
        ->and($tamil->draft_html)->toContain('தமிழ்')
        ->and(
            ThemeLayout::query()
                ->where('theme_slug', 'slsc')
                ->where('region', ThemeLayoutRegion::Header->value)
                ->count(),
        )->toBe(3);
});

it('publishes the current localized draft without changing it during later draft edits', function (): void {
    $actor = User::factory()->create();
    $actor->givePermissionTo('theme-layouts.manage');

    $service = app(ThemeLayoutService::class);

    $layout = $service->saveDraft(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Footer,
        locale: ThemeLayoutLocale::English,
        actor: $actor,
        html: '<footer>[[copyright_year]]</footer>',
        css: '.site-footer { color: #ffffff; }',
    );

    $published = $service->publish(
        layout: $layout,
        actor: $actor,
    );

    expect($published->status)->toBe(ThemeLayoutStatus::Published)
        ->and($published->locale)->toBe(ThemeLayoutLocale::English)
        ->and($published->published_html)->toBe($published->draft_html)
        ->and($published->revision_number)->toBe(2)
        ->and($published->revisions()->count())->toBe(2);

    $updatedDraft = $service->saveDraft(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Footer,
        locale: ThemeLayoutLocale::English,
        actor: $actor,
        html: '<footer>Updated [[copyright_year]]</footer>',
        css: '.site-footer { color: #f8fafc; }',
    );

    expect($updatedDraft->published_html)
        ->toBe('<footer>[[copyright_year]]</footer>')
        ->and($updatedDraft->draft_html)->toContain('Updated')
        ->and($updatedDraft->status)->toBe(ThemeLayoutStatus::Published);
});

it('rejects users without the dedicated permission', function (): void {
    $actor = User::factory()->create();

    app(ThemeLayoutService::class)->saveDraft(
        themeSlug: 'slsc',
        region: ThemeLayoutRegion::Header,
        locale: ThemeLayoutLocale::English,
        actor: $actor,
        html: '<header>[[site_name]]</header>',
        css: '',
    );
})->throws(AuthorizationException::class);

it('rejects an unavailable theme', function (): void {
    $actor = User::factory()->create();
    $actor->givePermissionTo('theme-layouts.manage');

    app(ThemeLayoutService::class)->saveDraft(
        themeSlug: 'missing-theme',
        region: ThemeLayoutRegion::Header,
        locale: ThemeLayoutLocale::English,
        actor: $actor,
        html: '<header>[[site_name]]</header>',
        css: '',
    );
})->throws(ValidationException::class);
