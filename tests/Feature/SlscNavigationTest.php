<?php

use App\Models\ThemeLayout;

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
});

it('renders the supplied Corps navigation for each public language', function (string $locale, array $labels, int $links): void {
    $home = $locale === 'en' ? route('home') : route('home.localized', ['locale' => $locale]);
    $response = $this->get($home)->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $panel = $xpath->query('//*[@id="navPanel"]')->item(0);
    expect($panel)->not->toBeNull();

    $topLabels = [];
    foreach ($xpath->query('./a | ./details/summary', $panel) as $item) {
        $topLabels[] = preg_replace('/\s+/u', ' ', trim($item->textContent));
    }
    expect($topLabels)->toBe($labels)
        ->and($xpath->query('.//details', $panel)->length)->toBe(6)
        ->and($xpath->query('.//a', $panel)->length)->toBe($links);

    foreach ($xpath->query('.//a', $panel) as $index => $anchor) {
        $href = $anchor->getAttribute('href');
        if (str_starts_with($href, 'https://alt.army.lk/signaller/')) {
            expect($anchor->getAttribute('target'))->toBe('_blank')
                ->and($anchor->getAttribute('rel'))->toContain('noopener', 'noreferrer');
        } elseif ($index >= 1 && $index <= 8) {
            $appointments = json_decode(file_get_contents(base_path('themes/slsc/navigation/'.$locale.'.json')), true)[1]['children'];
            expect($href)->toBe(url($appointments[$index - 1]['href']));
        } elseif ($index === 10) {
            expect($href)->toBe($locale === 'si' ? url('/si/first-appoinment-holders') : url('/first-appoinment-holders'));
        } elseif ($index === 11) {
            expect($href)->toBe($locale === 'si' ? url('/si/regt-comdt-col-comdt') : url('/regt-comdt-col-comdt'));
        } elseif ($index === 12) {
            expect($href)->toBe($locale === 'si' ? url('/si/chief-signal-officers') : url('/chief-signal-officers'));
        } elseif ($index === 13) {
            expect($href)->toBe($locale === 'si' ? url('/si/directors-of-it') : url('/directors-of-it'));
        } elseif ($index === 14) {
            expect($href)->toBe($locale === 'si' ? url('/si/brigade-commanders') : url('/brigade-commanders'));
        } elseif ($index === 15) {
            expect($href)->toBe($locale === 'si' ? url('/si/1-bde-comds') : url('/1-bde-comds'));
        } elseif ($index === 16) {
            expect($href)->toBe($locale === 'si' ? url('/si/2-bde-comds') : url('/2-bde-comds'));
        } elseif ($index === 17) {
            expect($href)->toBe($locale === 'si' ? url('/si/centre-commandants') : url('/centre-commandants'));
        } elseif ($index === 18) {
            expect($href)->toBe($locale === 'si' ? url('/si/regimental-adjutants') : url('/regimental-adjutants'));
        } elseif ($index === 19) {
            expect($href)->toBe($locale === 'si' ? url('/si/regimental-sergeant-major') : url('/regimental-sergeant-major'));
        } elseif ($index === 20) {
            expect($href)->toBe($locale === 'si' ? url('/si/organization') : url('/organization'));
        } else {
            expect($href)->toBe($index === 0 ? $home : $home.'#');
        }
    }

    $response->assertSee('data-open-search-modal', false)
        ->assertSee('id="mobileMenuBtn"', false)
        ->assertSee('/themes/slsc/assets/css/navigation.css', false)
        ->assertSee('/themes/slsc/assets/images/logo/signals-logo.png', false);
})->with([
    'English' => ['en', ['Home', 'Appointments', 'History', 'Organization', 'Magazine', 'Sports', 'Gallery', 'Contact'], 36],
    'Sinhala' => ['si', ['මුල් පිටුව', 'පත්වීම්', 'ඉතිහාසය', 'සංවිධානය', 'සගරාව', 'ක්‍රීඩා', 'මාධ්‍ය එකතුව', 'අමතන්න'], 34],
]);

it('preserves an administrator published header instead of replacing it with the default navigation', function (): void {
    ThemeLayout::query()->create([
        'theme_slug' => 'slsc', 'region' => 'header', 'locale' => 'en',
        'status' => 'published', 'published_html' => '<header id="approved-custom-header">Approved header</header>',
    ]);

    $this->get(route('home'))->assertOk()->assertSee('id="approved-custom-header"', false)
        ->assertDontSee('data-slsc-nav-group', false);
});
