<?php

use App\Enums\NewsLocale;
use App\Enums\PageLocale;
use App\Enums\ThemeLayoutLocale;
use App\Livewire\Admin\News\NewsCreate;
use App\Livewire\Admin\News\NewsEdit;
use App\Livewire\Admin\News\NewsIndex;
use App\Livewire\Admin\Pages\PageCreate;
use App\Livewire\Admin\Pages\PageEdit;
use App\Livewire\Admin\Pages\PageIndex;
use App\Livewire\Admin\Settings\SiteSettingsIndex;
use App\Livewire\Admin\ThemeLayouts\ThemeLayoutIndex;
use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\ThemeLayout;
use App\Models\User;
use App\Services\DefaultRoleService;
use App\Services\NavigationEditorService;
use App\Services\SiteLanguageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'school-of-signals');
    Permission::findOrCreate('settings.manage', 'web');
    Permission::findOrCreate('theme-layouts.manage', 'web');
    Permission::findOrCreate('pages.view', 'web');
    Permission::findOrCreate('news.view', 'web');
    $this->editor = User::factory()->create();
    $this->editor->givePermissionTo(['settings.manage', 'theme-layouts.manage', 'pages.view', 'news.view']);
    $this->actingAs($this->editor);
});

it('uses the existing theme defaults until languages are saved', function (): void {
    expect(app(SiteLanguageService::class)->codes())->toBe(['en', 'si'])
        ->and(SiteSetting::query()->count())->toBe(0);

    Livewire::test(SiteSettingsIndex::class)
        ->assertSet('enabledLocales', ['en', 'si'])
        ->assertSee('Site Languages')
        ->assertSee('site-language-ta', false);

    Livewire::test(ThemeLayoutIndex::class)->assertDontSee('தமிழ்');
    expect(app(SiteLanguageService::class)->choices(PageLocale::cases()))->toBe([PageLocale::English, PageLocale::Sinhala])
        ->and(app(SiteLanguageService::class)->choices(NewsLocale::cases()))->toBe([NewsLocale::English, NewsLocale::Sinhala]);
});

it('enables Tamil from settings across public pages news previews and header footer editors', function (): void {
    $page = Page::factory()->published()->create(['locale' => 'ta', 'title' => 'Tamil page', 'slug' => 'tamil-page']);
    $news = News::factory()->published()->create(['locale' => 'ta', 'title' => 'Tamil news', 'slug' => 'tamil-news']);

    $this->get('/ta')->assertNotFound();
    $this->get('/ta/news')->assertNotFound();
    $this->get('/ta/pages/'.$page->slug)->assertNotFound();

    Livewire::test(SiteSettingsIndex::class)
        ->set('enabledLocales', ['ta', 'en', 'si'])->call('save')->assertHasNoErrors()
        ->assertSet('enabledLocales', ['en', 'si', 'ta']);

    expect(SiteSetting::current()->enabled_locales)->toBe(['en', 'si', 'ta']);
    $this->get('/ta')->assertOk()->assertSee('lang="ta"', false);
    $this->get('/ta/news')->assertOk()->assertSee('Tamil news');
    $this->get('/ta/news/'.$news->slug)->assertOk();
    $this->get('/ta/pages/'.$page->slug)->assertOk()->assertSee('Tamil page');
    $this->get('/')->assertOk()->assertSee('தமிழ்');

    Livewire::test(ThemeLayoutIndex::class)->assertSee('தமிழ்')
        ->call('selectLocale', 'ta')->assertHasNoErrors()->assertSet('locale', 'ta');

    $preview = app(NavigationEditorService::class)->render('<header>[[language_switcher]]</header>', '', 'en', 'school-of-signals');
    expect($preview['html'])->toContain('data-lang="ta"');
});

it('hides disabled languages from switchers and SEO while retaining all content', function (): void {
    SiteSetting::current()->update(['enabled_locales' => ['en', 'si', 'ta']]);
    $englishPage = Page::factory()->published()->create(['locale' => 'en', 'slug' => 'english-version']);
    $tamilPage = Page::factory()->published()->create(['locale' => 'ta', 'translation_group' => $englishPage->translation_group, 'slug' => 'tamil-version']);
    $englishNews = News::factory()->published()->create(['locale' => 'en', 'slug' => 'english-news']);
    $tamilNews = News::factory()->published()->create(['locale' => 'ta', 'translation_group' => $englishNews->translation_group, 'slug' => 'tamil-news']);
    $layout = ThemeLayout::query()->create([
        'theme_slug' => 'school-of-signals', 'region' => 'footer', 'locale' => 'ta',
        'draft_html' => '<footer>Tamil draft</footer>', 'published_html' => '<footer>Tamil footer</footer>',
        'status' => 'published', 'published_at' => now(),
    ]);

    $this->get('/en/pages/english-version')->assertOk()->assertSee('hreflang="ta"', false);
    $this->get('/news/english-news')->assertOk()->assertSee('hreflang="ta"', false);

    Livewire::test(SiteSettingsIndex::class)->set('enabledLocales', ['en', 'si'])->call('save')->assertHasNoErrors();
    foreach (['/', '/news', '/en/pages/english-version', '/news/english-news'] as $url) {
        $this->get($url)->assertOk()->assertDontSee('data-lang="ta"', false)->assertDontSee('hreflang="ta"', false)->assertDontSee('தமிழ்');
    }
    foreach (['/ta', '/ta/news', '/ta/news/tamil-news', '/ta/pages/tamil-version'] as $url) {
        $this->get($url)->assertNotFound();
    }
    expect($tamilPage->fresh())->not->toBeNull()
        ->and($tamilNews->fresh())->not->toBeNull()
        ->and($layout->fresh()->draft_html)->toBe('<footer>Tamil draft</footer>')
        ->and($layout->fresh()->published_html)->toBe('<footer>Tamil footer</footer>');

    Livewire::test(SiteSettingsIndex::class)->set('enabledLocales', ['en', 'si', 'ta'])->call('save')->assertHasNoErrors();
    $this->get('/ta/pages/tamil-version')->assertOk();
    $this->get('/ta/news/tamil-news')->assertOk();
});

it('filters administrator language choices and rejects selecting a disabled layout language', function (): void {
    Livewire::test(ThemeLayoutIndex::class)->assertDontSee('தமிழ்')
        ->call('selectLocale', 'ta')->assertHasErrors('locale')->assertSet('locale', 'en');
    Livewire::test(PageIndex::class)->assertViewHas('locales', fn (array $locales): bool => array_column($locales, 'value') === ['en', 'si']);
    Livewire::test(NewsIndex::class)->assertViewHas('locales', fn (array $locales): bool => array_column($locales, 'value') === ['en', 'si']);
    expect(app(SiteLanguageService::class)->choices(ThemeLayoutLocale::cases(), 'ta'))->toHaveCount(3);
});

it('requires English and validates supported language codes without altering saved settings', function (array $codes, string $error): void {
    SiteSetting::current()->update(['enabled_locales' => ['en', 'si']]);
    Livewire::test(SiteSettingsIndex::class)->set('enabledLocales', $codes)->call('save')->assertHasErrors($error);
    expect(SiteSetting::current()->enabled_locales)->toBe(['en', 'si']);
})->with([
    'empty' => [[], 'enabledLocales'],
    'missing fallback' => [['si', 'ta'], 'enabledLocales'],
    'unsupported language' => [['en', 'fr'], 'enabledLocales.1'],
    'duplicate' => [['en', 'en'], 'enabledLocales.0'],
]);

it('checks settings permission before changing languages', function (): void {
    $component = Livewire::test(SiteSettingsIndex::class);
    $this->editor->revokePermissionTo('settings.manage');
    $component->set('enabledLocales', ['en', 'si', 'ta'])->call('save')->assertForbidden();
    expect(SiteSetting::current()->enabled_locales)->toBeNull();
});

it('rejects disabled languages on new content forms and translation creation URLs', function (): void {
    foreach (['pages.create', 'news.create'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $this->editor->givePermissionTo($permission);
    }
    $page = Page::factory()->create(['locale' => 'en']);
    $news = News::factory()->create(['locale' => 'en']);
    Livewire::test(PageCreate::class)->set('locale', 'ta')->call('save')->assertHasErrors('locale');
    Livewire::test(NewsCreate::class)->set('locale', 'ta')->call('save')->assertHasErrors('locale');
    Livewire::test(PageCreate::class, ['pageId' => $page->id, 'locale' => 'ta'])->assertNotFound();
    Livewire::test(NewsCreate::class, ['newsId' => $news->id, 'locale' => 'ta'])->assertNotFound();

    SiteSetting::current()->update(['enabled_locales' => ['en', 'si', 'ta']]);
    Livewire::test(PageCreate::class, ['pageId' => $page->id, 'locale' => 'ta'])->assertSet('locale', 'ta');
    Livewire::test(NewsCreate::class, ['newsId' => $news->id, 'locale' => 'ta'])->assertSet('locale', 'ta');
});

it('keeps existing hidden-language content editable without exposing it publicly', function (): void {
    foreach (['pages.update', 'news.update'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $this->editor->givePermissionTo($permission);
    }
    $page = Page::factory()->create(['locale' => 'ta']);
    $news = News::factory()->create(['locale' => 'ta']);
    Livewire::test(PageEdit::class, ['page' => $page])->assertViewHas('locales', fn (array $locales): bool => in_array(PageLocale::Tamil, $locales, true));
    Livewire::test(NewsEdit::class, ['news' => $news])->assertViewHas('locales', fn (array $locales): bool => in_array(NewsLocale::Tamil, $locales, true));
    $this->get('/ta')->assertNotFound();
});

it('gives existing administrator presets access to language settings without expanding content roles', function (): void {
    app(DefaultRoleService::class)->install();
    foreach (['Super Administrator', 'Site Administrator'] as $role) {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);
        Livewire::test(SiteSettingsIndex::class)->assertSee('Site Languages');
    }
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');
    $this->actingAs($editor);
    Livewire::test(SiteSettingsIndex::class)->assertForbidden();
});
