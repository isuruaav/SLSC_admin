<?php

use App\Enums\ThemeLayoutRegion;
use App\Enums\ThemeLayoutStatus;
use App\Models\ThemeLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a theme specific draft layout with enum casts', function (): void {
    $layout = ThemeLayout::query()->create([
        'theme_slug' => 'slsc',
        'region' => ThemeLayoutRegion::Header->value,
        'draft_html' => '<header>[[primary_menu]]</header>',
        'draft_css' => '.site-header { display: flex; }',
        'status' => ThemeLayoutStatus::Draft->value,
    ]);

    expect($layout->uuid)->not->toBeEmpty()
        ->and($layout->region)->toBe(ThemeLayoutRegion::Header)
        ->and($layout->status)->toBe(ThemeLayoutStatus::Draft)
        ->and($layout->revision_number)->toBe(0);
});
