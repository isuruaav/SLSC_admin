<?php

use App\Services\ThemeViewResolver;

it('resolves installed active theme views', function (): void {
    config()->set('awcms.active_theme', 'slsc');

    $resolver = app(ThemeViewResolver::class);

    expect($resolver->resolve('home'))->toBeString()->toEndWith('home.blade.php')
        ->and($resolver->resolve('pages.show'))->toBeString()->toEndWith('pages'.DIRECTORY_SEPARATOR.'show.blade.php');
});

it('returns null when no active theme is configured', function (): void {
    config()->set('awcms.active_theme', null);

    expect(app(ThemeViewResolver::class)->resolve('home'))->toBeNull();
});

it('rejects missing and unsafe theme views', function (): void {
    config()->set('awcms.active_theme', 'slsc');

    $resolver = app(ThemeViewResolver::class);

    expect($resolver->resolve('missing.view'))->toBeNull()
        ->and($resolver->resolve('../home'))->toBeNull()
        ->and($resolver->resolve('pages/../../home'))->toBeNull();
});
