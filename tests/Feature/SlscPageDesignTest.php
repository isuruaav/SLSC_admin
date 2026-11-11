<?php

use App\Models\Page;

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
    config()->set('awcms.theme_locales.slsc', ['en', 'si']);
});

it('provides a consistent accessible page shell across public sections', function (string $locale): void {
    $prefix = $locale === 'en' ? '' : '/si';
    foreach ([
        '/school-leadership', '/school-leadership/history',
        '/first-appoinment-holders', '/brigade-commanders', '/chief-signal-officers',
        '/organization', '/galleries', '/news', '/signaller-magazines',
        '/courses', '/contact', '/register-interest', '/search',
    ] as $path) {
        $response = $this->get($prefix.$path)->assertOk();
        $response->assertSee('slsc-inner-page', false)
            ->assertSee('/css/inner-pages.css?v=', false)
            ->assertSee('class="slsc-page-trail"', false);

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($document);

        expect($xpath->query('//main')->length)->toBe(1, $prefix.$path)
            ->and($xpath->query('//h1')->length)->toBe(1, $prefix.$path)
            ->and($xpath->query('//div[@class="slsc-page-trail"]//span[@aria-current="page"]')->length)->toBe(1);

        $home = $xpath->query('//div[@class="slsc-page-trail"]//a')->item(0);
        expect($home->getAttribute('href'))->toBe(url($prefix ?: '/'));
    }
})->with(['English' => 'en', 'Sinhala' => 'si']);

it('keeps CMS content and scoped custom styles inside the shared page shell', function (): void {
    $page = Page::factory()->published()->create([
        'locale' => 'en',
        'title' => 'Signal Flag',
        'slug' => 'signal-flag-design',
        'show_title' => true,
        'content' => '<p class="flag-description">Official flag information.</p>',
        'custom_css' => '.flag-description { font-weight: 600; }',
    ]);

    $response = $this->get('/pages/'.$page->slug)->assertOk();
    $response->assertSee('data-page-heading', false)
        ->assertSee('Official flag information.')
        ->assertSee('data-page-custom-css="'.$page->id.'"', false);

    expect(substr_count($response->getContent(), '<main'))->toBe(1);
});

it('preserves hidden CMS titles and leaves home page styling independent', function (): void {
    $page = Page::factory()->published()->create([
        'locale' => 'en', 'title' => 'Custom Program', 'slug' => 'custom-program-design',
        'show_title' => false, 'excerpt' => null,
        'content' => '<h1>Program heading</h1><p>Program details.</p>',
    ]);

    $response = $this->get('/pages/'.$page->slug)->assertOk();
    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
    $this->get('/')->assertOk()->assertSee('slsc-home', false)
        ->assertDontSee('class="slsc-page-trail"', false);
});
