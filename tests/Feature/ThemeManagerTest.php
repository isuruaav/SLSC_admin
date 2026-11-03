<?php

use App\Services\ThemeManager;

test('theme manager discovers the active theme manifest', function (): void {
    $theme = app(ThemeManager::class)->find('slsc');

    expect($theme)
        ->not->toBeNull()
        ->and($theme['name'])->toBe('Sri Lanka Signal Corps')
        ->and($theme['slug'])->toBe('slsc')
        ->and($theme['supports'])->toContain('pages', 'news', 'multilingual');
});

test('theme manager rejects an unsafe theme slug', function (): void {
    expect(app(ThemeManager::class)->find('../slsc'))
        ->toBeNull();
});
