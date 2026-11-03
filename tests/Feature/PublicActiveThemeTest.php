<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses the default public home when no active theme is configured', function (): void {
    config()->set('awcms.active_theme', null);

    $this->get(route('home'))
        ->assertOk()
        ->assertViewIs('public.home');
});

it('uses an installed active theme home view', function (): void {
    config()->set('awcms.active_theme', 'slsc');

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('SRI LANKA SIGNAL CORPS');
});

it('falls back safely when the configured theme is unavailable', function (): void {
    config()->set('awcms.active_theme', 'missing-theme');

    $this->get(route('home'))
        ->assertOk()
        ->assertViewIs('public.home');
});
