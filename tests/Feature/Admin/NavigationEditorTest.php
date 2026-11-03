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

    expect($english['defaultHtml'])->toContain('[[site_url:/]]', '>Home</span>', '>Courses</span>', 'fa-solid fa-house', '[[mobile_menu_toggle]]')
        ->and($sinhala['defaultHtml'])->toContain('[[site_url:/si/courses]]', __('public.home', [], 'si'))
        ->and(app(ThemeLayoutSanitizer::class)->sanitizeCss($english['baseCss']))->toContain('.aw-nav-submenu')
        ->and(ThemeLayout::query()->count())->toBe(0);
});

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
        ->assertReturned(fn (string $preview): bool => str_contains($preview, 'assets/images/logo.png')
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
