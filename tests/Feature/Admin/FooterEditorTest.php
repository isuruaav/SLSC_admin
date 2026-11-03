<?php

use App\Livewire\Admin\ThemeLayouts\ThemeLayoutIndex;
use App\Models\Page;
use App\Models\ThemeLayout;
use App\Models\User;
use App\Services\FooterEditorService;
use App\Services\ThemeLayoutSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::findOrCreate('theme-layouts.manage', 'web');
    config()->set('awcms.active_theme', 'slsc');
    $this->editor = User::factory()->create();
    $this->editor->givePermissionTo('theme-layouts.manage');
    $this->actingAs($this->editor);
});

it('opens the footer visual builder and keeps header and footer controls separate', function (): void {
    $component = Livewire::test(ThemeLayoutIndex::class)
        ->assertSee('Navigation editor')
        ->assertDontSee('Footer visual editor')
        ->call('selectRegion', 'footer')
        ->assertSee('Footer visual editor')
        ->assertSee('Footer columns')
        ->assertSee('Column blocks')
        ->assertSee('Footer appearance')
        ->assertSee('Footer preview')
        ->assertDontSee('Navigation editor mode')
        ->assertDontSee('Expand mobile menu')
        ->assertSee('sandbox="allow-same-origin"', false)
        ->assertSet('layoutHtml', '');

    if (getenv('FOOTER_EDITOR_WRITE_FIXTURE') === '1') {
        $component->set('layoutHtml', '<footer class="aw-footer"><div class="aw-footer-grid"><section><h2>School</h2><p>Welcome</p></section><section><h2>Quick Links</h2><a href="[[site_url:/]]">Home</a><a href="[[site_url:/courses]]">Courses</a></section><section><h2>Contact</h2>[[contact_details]]</section></div><div class="aw-footer-bottom"><p>© [[copyright_year]] [[site_name]]</p></div></footer>');
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($directory.'/footer-editor.html', $component->html());
    }

    $component->call('selectRegion', 'header')
        ->assertSee('Navigation editor mode')
        ->assertDontSee('Footer visual editor');

    expect(ThemeLayout::query()->count())->toBe(0);
});

it('offers localized safe footer starters and published pages without altering layouts', function (): void {
    Page::factory()->published()->create(['locale' => 'si', 'title' => 'Sinhala page', 'slug' => 'sinhala-page']);
    Page::factory()->published()->create(['locale' => 'en', 'title' => 'English page']);
    Page::factory()->create(['locale' => 'si', 'title' => 'Draft page']);
    $config = app(FooterEditorService::class)->configuration('si', 'slsc');

    expect($config['defaultHtml'])->toContain('<footer', '[[site_logo]]', '[[contact_details]]', '[[social_links]]', '[[copyright_year]]', '[[site_url:/si/courses]]', __('public.quick_links', [], 'si'))
        ->and($config['pages'])->toBe([['label' => 'Sinhala page', 'href' => '[[site_url:/si/pages/sinhala-page]]']])
        ->and(app(ThemeLayoutSanitizer::class)->sanitizeHtml($config['defaultHtml']))->toContain('aw-footer-grid', '[[social_links]]')
        ->and(app(ThemeLayoutSanitizer::class)->sanitizeCss($config['baseCss']))->toContain('.aw-footer-grid')
        ->and(ThemeLayout::query()->count())->toBe(0);
});

it('previews the selected footer securely without saving it', function (): void {
    Livewire::test(ThemeLayoutIndex::class)
        ->call('selectRegion', 'footer')
        ->set('layoutHtml', '<footer>Existing source</footer>')
        ->call('previewLayout', '<footer><h2>[[site_name]]</h2><a href="[[site_url:/contact]]" onclick="alert(1)">Get in touch</a></footer>', '.aw-footer { color: #123456; }')
        ->assertHasNoErrors()
        ->assertSet('layoutHtml', '<footer>Existing source</footer>')
        ->assertReturned(fn (string $preview): bool => str_contains($preview, 'http://localhost/contact')
            && str_contains($preview, "script-src 'none'")
            && str_contains($preview, 'color: #123456')
            && ! str_contains($preview, 'onclick'))
        ->call('previewLayout', '<footer><script>alert(1)</script></footer>', '')
        ->assertHasErrors('layout_html')
        ->call('previewLayout', '<footer>Fixed</footer>', '')
        ->assertHasNoErrors();

    expect(ThemeLayout::query()->count())->toBe(0);
});

it('saves the current footer source independently and publishes only when requested', function (): void {
    $component = Livewire::test(ThemeLayoutIndex::class)
        ->set('layoutHtml', '<header>Keep this header</header>')->call('saveAndPublish')
        ->call('selectRegion', 'footer')
        ->set('layoutHtml', '<footer><section><h2>Training links</h2><a href="[[site_url:/courses]]">Courses</a></section><p>© [[copyright_year]] [[site_name]]</p></footer>')
        ->set('layoutCss', '.aw-footer { --aw-footer-gap: 0px; }')
        ->call('saveDraft')->assertHasNoErrors();

    $footer = ThemeLayout::query()->where('region', 'footer')->sole();
    expect($footer->published_html)->toBeNull();

    $component->call('selectLocale', 'si')->assertSet('layoutHtml', '')
        ->set('layoutHtml', '<footer>Sinhala footer</footer>')->call('saveDraft')->assertHasNoErrors()
        ->call('selectLocale', 'en')->assertSet('layoutHtml', $footer->draft_html)
        ->call('publish')->assertHasNoErrors();

    expect($footer->fresh()->published_html)->toBe($footer->draft_html)
        ->and(ThemeLayout::query()->where('region', 'header')->sole()->published_html)->toBe('<header>Keep this header</header>')
        ->and(ThemeLayout::query()->where('region', 'footer')->where('locale', 'si')->sole()->published_html)->toBeNull();

    $this->get(route('home'))->assertOk()->assertSee('Training links')->assertSee('http://localhost/courses');
});

it('checks permission on the shared footer preview endpoint', function (): void {
    $component = Livewire::test(ThemeLayoutIndex::class)->call('selectRegion', 'footer');
    $this->editor->revokePermissionTo('theme-layouts.manage');
    $component->call('previewLayout', '<footer>Preview</footer>', '')->assertForbidden();
});
