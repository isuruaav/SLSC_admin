<?php

use App\Livewire\Admin\ThemeLayouts\ThemeLayoutIndex;
use App\Models\Page;
use App\Models\ThemeLayout;
use App\Models\User;
use App\Services\NavigationEditorService;
use App\Services\ThemeLayoutSanitizer;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::findOrCreate('theme-layouts.manage', 'web');
    config()->set('awcms.active_theme', 'slsc');
    $this->editor = User::factory()->create();
    $this->editor->givePermissionTo('theme-layouts.manage');
    $this->actingAs($this->editor);
});

it('offers localized default links without creating or replacing stored layouts', function (): void {
    $service = app(NavigationEditorService::class);
    $english = $service->configuration('en', 'slsc');
    $sinhala = $service->configuration('si', 'slsc');

    expect($english['defaultHtml'])->toContain('[[site_url:/]]', '>Home</span>', '>Appointments</span>', 'fa-solid fa-house', '[[mobile_menu_toggle]]')
        ->and($sinhala['defaultHtml'])->toContain('[[site_url:/si]]', 'මුල් පිටුව')
        ->and(app(ThemeLayoutSanitizer::class)->sanitizeCss($english['baseCss']))->toContain('.aw-nav-submenu')
        ->and(ThemeLayout::query()->count())->toBe(0);
});

it('offers generic fallback starter links when a theme has no custom navigation', function (): void {
    $service = app(NavigationEditorService::class);
    $english = $service->configuration('en', 'generic-fallback-theme');
    $sinhala = $service->configuration('si', 'generic-fallback-theme');

    expect($english['defaultHtml'])->toContain('[[site_url:/]]', '>Home</span>', '>Courses</span>', 'fa-solid fa-house', '[[mobile_menu_toggle]]')
        ->and($sinhala['defaultHtml'])->toContain('[[site_url:/si/courses]]', __('public.home', [], 'si'));
});

it('offers visual header branding and layout controls alongside the shared HTML and CSS source', function (): void {
    Livewire::test(ThemeLayoutIndex::class)
        ->assertSee('Header layout &amp; branding', false)
        ->assertSee('Apply reference layout')
        ->assertSee('Logo, menu and actions in one row')
        ->assertSee('Logo above the menu')
        ->assertSee('x-on:input="updateHeader(\'brandTitle\', $event.target.value)"', false)
        ->assertSee('x-on:change="updateHeader(\'portalHref\', $event.target.value)"', false)
        ->assertSee('Header HTML')->assertSee('Header CSS');
});

it('publishes independently editable reference headers in English and Sinhala', function (): void {
    $service = app(NavigationEditorService::class);
    foreach (['en' => 'Our English Corps', 'si' => 'අපගේ සංඥා බලකාය'] as $locale => $title) {
        $config = $service->configuration($locale, 'slsc');
        $html = str_replace($locale === 'en' ? '>Sri Lanka Signal Corps<' : '>ශ්‍රී ලංකා සංඥා බලකාය<', '>'.$title.'<', $config['defaultHtml']);
        $css = $config['baseCss']."\n.aw-brand-title { letter-spacing: 1px; }";
        Livewire::test(ThemeLayoutIndex::class)->call('selectLocale', $locale)
            ->set('layoutHtml', $html)->set('layoutCss', $css)->call('saveAndPublish')->assertHasNoErrors();
        $this->get($locale === 'en' ? route('home') : route('home.localized', ['locale' => $locale]))
            ->assertOk()->assertSee($title)->assertSee('aw-header-reference')->assertSee('aw-topbar')
            ->assertSee('data-language-option', false)->assertSee('data-open-search-modal', false);
    }
    expect(ThemeLayout::query()->where('locale', 'en')->sole()->published_html)->toContain('Our English Corps')->not->toContain('අපගේ සංඥා බලකාය')
        ->and(ThemeLayout::query()->where('locale', 'si')->sole()->published_html)->toContain('අපගේ සංඥා බලකාය');
    Livewire::test(ThemeLayoutIndex::class)->assertSet('layoutCss', fn (string $css): bool => str_contains($css, '.aw-brand-title { letter-spacing: 1px; }'));
});

it('publishes all eight appointment profile links in desktop and mobile navigation', function (string $locale): void {
    $config = app(NavigationEditorService::class)->configuration($locale, 'slsc');
    Livewire::test(ThemeLayoutIndex::class)
        ->call('selectLocale', $locale)
        ->set('layoutHtml', $config['defaultHtml'])
        ->set('layoutCss', $config['baseCss'])
        ->call('saveAndPublish')
        ->assertHasNoErrors();

    $response = $this->get($locale === 'en' ? route('home') : route('home.localized', ['locale' => $locale]))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $prefix = ($locale === 'si' ? '/si' : '').'/school-leadership/';
    $appointments = ['colonel-commandant', 'chief-signal-officer', 'director-it', 'commander-1-signal-brigade',
        'commander-2-signal-brigade', 'regimental-centre-commandant', 'regimental-adjutant', 'regimental-sergeant-major'];
    $expectedUrls = array_map(fn (string $appointment): string => url($prefix.$appointment), $appointments);

    foreach (['//nav[contains(@class, "aw-desktop-navigation")]', '//*[@id="mobileMenu"]'] as $menu) {
        $anchors = $xpath->query($menu.'//a[contains(@href, "/school-leadership/")]');
        $urls = [];
        foreach ($anchors as $anchor) {
            $urls[] = $anchor->getAttribute('href');
        }
        expect($urls)->toBe($expectedUrls);
    }
})->with(['en', 'si']);

it('only offers published pages in the selected language', function (): void {
    Page::factory()->published()->create(['locale' => 'en', 'title' => 'English Page', 'slug' => 'english-page']);
    Page::factory()->published()->create(['locale' => 'si', 'title' => 'Sinhala Page']);
    Page::factory()->create(['locale' => 'en', 'title' => 'Draft Page']);

    $config = app(NavigationEditorService::class)->configuration('en', 'slsc');

    expect($config['pages'])->toBe([
        ['label' => 'English Page', 'href' => '[[site_url:/en/pages/english-page]]'],
    ]);
});

it('renders a sanitized preview without changing the draft or publishing', function (): void {
    Livewire::test(ThemeLayoutIndex::class)
        ->call('previewNavigation', '<header><nav><a href="[[site_url:/courses]]" onclick="alert(1)">Training</a></nav></header>', '.aw-navigation { color: #123456; }')
        ->assertHasNoErrors()
        ->assertReturned(function (string $preview): bool {
            return str_contains($preview, 'http://localhost/courses')
                && str_contains($preview, 'Content-Security-Policy')
                && ! str_contains($preview, 'onclick')
                && str_contains($preview, 'color: #123456');
        });

    expect(ThemeLayout::query()->count())->toBe(0);
});

it('rejects executable source in preview and keeps the existing draft', function (): void {
    Livewire::test(ThemeLayoutIndex::class)
        ->set('layoutHtml', '<header>Existing draft</header>')
        ->call('previewNavigation', '<script>alert(1)</script>', '')
        ->assertHasErrors('layout_html')
        ->assertSet('layoutHtml', '<header>Existing draft</header>');
});

it('saves and publishes the latest shared editor source including nested submenus', function (): void {
    $html = '<header><nav class="aw-navigation"><a href="[[site_url:/]]">Home</a><div class="drop aw-nav-parent"><a href="#">About</a><div class="dropdown aw-nav-submenu"><a href="[[site_url:/en/pages/history]]">School History</a></div></div></nav></header>';

    Livewire::test(ThemeLayoutIndex::class)
        ->set('layoutHtml', $html)
        ->set('layoutCss', '.aw-navigation { --aw-nav-color: #123456; }')
        ->call('saveAndPublish')
        ->assertHasNoErrors();

    $layout = ThemeLayout::query()->sole();
    expect($layout->published_html)->toBe($layout->draft_html)
        ->toContain('School History', 'aw-nav-submenu')
        ->and($layout->published_css)->toBe('.aw-navigation { --aw-nav-color: #123456; }');

    $this->get(route('home'))->assertOk()->assertSee('School History')->assertSee('href="http://localhost/en/pages/history"', false);
});

it('keeps an intentionally empty menu after saving and reloading', function (): void {
    Livewire::test(ThemeLayoutIndex::class)
        ->set('layoutHtml', '<header>[[site_name]]<nav class="aw-navigation"></nav></header>')
        ->call('saveDraft')->assertHasNoErrors();

    Livewire::test(ThemeLayoutIndex::class)
        ->assertSet('layoutHtml', '<header>[[site_name]]<nav class="aw-navigation"></nav></header>');
});

it('checks layout permission again before rendering a preview or publishing', function (): void {
    $component = Livewire::test(ThemeLayoutIndex::class);
    $this->editor->revokePermissionTo('theme-layouts.manage');

    $component->call('previewNavigation', '<header>Preview</header>', '')->assertForbidden();

    expect(ThemeLayout::query()->count())->toBe(0);
});

it('offers icons that exist in the bundled Font Awesome stylesheet', function (): void {
    $config = app(NavigationEditorService::class)->configuration('en', 'slsc');
    $stylesheet = file_get_contents(base_path('themes/slsc/assets/fonts/fontawesome-7/css/all.min.css'));

    expect($config['icons'])->toHaveCount(30);
    foreach ($config['icons'] as $icon) {
        expect($stylesheet)->toContain('.'.str_replace('fa-solid ', '', $icon['class']));
    }
});

it('keeps decorative icon accessibility attributes when saving the shared source', function (): void {
    $html = '<header><nav><a href="[[site_url:/]]"><i class="aw-nav-icon fa-solid fa-house" aria-hidden="true"></i><span>Home</span></a></nav></header>';

    Livewire::test(ThemeLayoutIndex::class)->set('layoutHtml', $html)->call('saveDraft')->assertHasNoErrors();

    expect(ThemeLayout::query()->sole()->draft_html)->toContain('fa-solid fa-house', 'aria-hidden="true"');
});

it('includes the theme logo fallback and script-free same-origin font preview', function (): void {
    Livewire::test(ThemeLayoutIndex::class)
        ->assertSee('sandbox="allow-same-origin"', false)
        ->assertDontSee('allow-scripts')
        ->call('previewNavigation', '<header>[[site_logo]]<nav><a href="/">Home</a></nav></header>', '')
        ->assertHasNoErrors()
        ->assertReturned(fn (string $preview): bool => str_contains($preview, 'assets/images/logo/signals-logo.png')
            && str_contains($preview, "script-src 'none'")
            && str_contains($preview, 'fontawesome-7/css/all.min.css')
            && ! str_contains($preview, '<script'));
});

it('clears an invalid preview error after the user fixes the source', function (): void {
    Livewire::test(ThemeLayoutIndex::class)
        ->call('previewNavigation', '<script>unsafe</script>', '')->assertHasErrors('layout_html')
        ->call('previewNavigation', '<header><nav><a href="/">Fixed</a></nav></header>', '')
        ->assertHasNoErrors('layout_html');
});

it('uses the public theme stylesheets and language fonts inside the script-free preview', function (string $locale): void {
    $stylesheets = static function (string $html): array {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        $xpath = new DOMXPath($document);
        $urls = [];
        foreach ($xpath->query('//link[@rel="stylesheet"]') as $link) {
            $urls[] = $link->getAttribute('href');
        }

        return $urls;
    };
    $public = $this->get($locale === 'en' ? route('home') : route('home.localized', ['locale' => $locale]))->assertOk()->getContent();
    Livewire::test(ThemeLayoutIndex::class)->call('selectLocale', $locale)
        ->call('previewNavigation', '<header>[[site_logo]]<nav><a href="/">Home</a></nav></header>', '')
        ->assertHasNoErrors()->assertReturned(function (string $preview) use ($stylesheets, $public, $locale): bool {
            expect($stylesheets($preview))->toBe($stylesheets($public))
                ->and($preview)->toContain('fonts.googleapis.com', 'fonts.gstatic.com', "script-src 'none'")
                ->not->toContain('/assets/css/theme.css');
            $logoUrls = [];
            foreach ([$public, $preview] as $html) {
                $doc = new DOMDocument;
                @$doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
                $logoUrls[] = (new DOMXPath($doc))->query('//header//img')->item(0)?->getAttribute('src');
            }
            expect($logoUrls[1])->toBe($logoUrls[0])->not->toBeNull();
            if ($locale === 'si') {
                expect($preview)->toContain('class="js is-si"', '<body class="is-si" data-preview-current-url=');
            }

            return true;
        });
})->with(['en', 'si']);
