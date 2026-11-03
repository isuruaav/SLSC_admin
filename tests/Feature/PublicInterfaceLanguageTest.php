<?php

use App\Enums\ThemeLayoutRegion;
use App\Models\ContactMessage;
use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\ThemeLayout;
use App\Models\ThemeLayoutRevision;
use App\Services\ThemeLayoutRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
    SiteSetting::query()->create([
        'site_name' => 'School of Signals',
        'site_name_si' => 'සංඥා පාසල',
        'site_tagline' => 'Official school website',
        'site_tagline_si' => 'නිල පාසල් වෙබ් අඩවිය',
        'address' => 'Kandy',
        'address_si' => 'මහනුවර',
        'email' => 'school@example.com',
        'phone_primary' => '081 123 4567',
    ]);
});

it('localizes the public footer header contact section and navigation links', function (string $locale, string $quickLinks, string $rights, string $contactDescription): void {
    $response = $this->get(route('home.localized', ['locale' => $locale]))->assertOk();
    $response->assertSeeText($quickLinks)->assertSeeText($rights)->assertSeeText($contactDescription);

    $homeUrl = $locale === 'si' ? route('home.localized', ['locale' => 'si']) : route('home');
    $response->assertSee('href="'.$homeUrl.'"', false);

    if ($locale === 'si') {
        $response->assertDontSeeText('Quick Links')->assertDontSeeText('Stay connected with the Corps.');
    }
})->with([
    ['en', 'Quick Links', 'All Rights Reserved.', 'Stay connected with the Corps.'],
    ['si', 'කඩිනම් සබැඳි', 'සියලු හිමිකම් ඇවිරිණි.', 'සංඥා බලකාය සමඟ සම්බන්ධ වන්න.'],
]);

it('serves the local Sinhala font from the application base path on the generic public layout', function (): void {
    config()->set('awcms.active_theme', 'unavailable-theme');

    $this->get(route('home.localized', ['locale' => 'si']))
        ->assertOk()
        ->assertSee('/themes/slsc/assets/fonts/NotoSerifSinhala-Regular.woff2', false)
        ->assertSee('AWCMS Noto Serif Sinhala', false);
});

it('localizes news listings empty states and article controls', function (): void {
    $indexUrl = route('news.index.localized', ['locale' => 'si']);
    $this->get($indexUrl)->assertOk()->assertSeeText('පුවත් නොමැත')->assertDontSeeText('No News Available');

    $news = News::factory()->published()->create(['locale' => 'si', 'is_featured' => true]);
    News::factory()->published()->create(['locale' => 'si', 'category_id' => $news->category_id]);

    $this->get($indexUrl)->assertOk()->assertSeeText('විශේෂාංග')->assertSeeText('තවදුරටත් කියවන්න')
        ->assertDontSeeText('Read More');
    $this->get(route('news.show.localized', ['locale' => 'si', 'slug' => $news->slug]))
        ->assertOk()->assertSeeText('සියලු පුවත්')->assertSeeText('අදාළ පුවත්')
        ->assertSeeText('ලිපිය කියවන්න')->assertDontSee('aria-label="Previous related news"', false)
        ->assertDontSee('>Featured</span>', false);
});

it('localizes pagination on sinhala news pages', function (): void {
    News::factory()->count(13)->published()->create(['locale' => 'si']);
    $this->get(route('news.index.localized', ['locale' => 'si']))->assertOk()
        ->assertSee('aria-label="'.__('public.news_page.pagination', [], 'si').'"', false)
        ->assertSeeText('ඊළඟ')->assertSeeText('1 / 2')
        ->assertSee('/si/news?page=2', false)
        ->assertDontSeeText('Showing')->assertDontSeeText('results');
});

it('keeps contact forms validation and submission in sinhala', function (): void {
    $url = route('contact.create.localized', ['locale' => 'si']);
    $storeUrl = route('contact.store.localized', ['locale' => 'si']);
    $this->get($url)->assertOk()->assertSeeText('අප හා සම්බන්ධ වන්න')->assertSeeText('මහනුවර')
        ->assertSee('action="'.$storeUrl.'"', false)->assertSee('lang="si"', false);

    $this->from($url)->post($storeUrl, [])->assertRedirect($url)
        ->assertSessionHasErrors(['name' => 'නම ඇතුළත් කරන්න.']);

    $this->from($url)->post($storeUrl, [
        'name' => 'Test Visitor', 'email' => 'visitor@example.com', 'subject' => 'Inquiry',
        'message' => 'Please send course information.', 'website' => '',
    ])->assertRedirect($url)->assertSessionHasNoErrors();
    $this->get($url)->assertSeeText('ඔබගේ පණිවිඩය ලැබී ඇත.');
    expect(ContactMessage::query()->count())->toBe(1);
});

it('localizes the default public layout too', function (): void {
    config()->set('awcms.active_theme', null);
    $this->get(route('home.localized', ['locale' => 'si']))->assertOk()
        ->assertSeeText('සියලු හිමිකම් ඇවිරිණි.')->assertDontSeeText('All rights reserved.')
        ->assertSee('href="'.route('news.index.localized', ['locale' => 'si']).'"', false);
});

it('repairs the stored sinhala footer without changing english or custom content', function (): void {
    SiteSetting::query()->update([
        'site_name_si' => null,
        'site_tagline' => 'Sri Lanka Signal Corps | School of Signals, Kandy',
        'site_tagline_si' => 'අනුමත ආදර්ශ පාඨය',
    ]);
    $englishPage = Page::factory()->published()->create(['locale' => 'en', 'slug' => 'about-school']);
    Page::factory()->published()->create([
        'locale' => 'si', 'slug' => 'school-details-si', 'translation_group' => $englishPage->translation_group,
    ]);
    $englishHtml = '<footer class="managed-school-footer"><nav class="managed-school-footer-links"><h2>Quick Links</h2>'
        .'<a href="[[site_url:/]]">Home</a><a href="[[site_url:/en/pages/about-school]]">About Us</a>'
        .'<a href="[[site_url:/news]]">News</a><a href="[[site_url:/galleries]]">Gallery</a></nav></footer>';
    $sinhalaHtml = '<footer class="managed-school-footer"><h2>Quick Links</h2>[[primary_menu]]'
        .'<p>Sri Lanka Army</p><p>Official information, training updates, publications and'."\n"
        .' media from the Sri Lanka School of Signals.</p><h2>Contact</h2><h2>Follow Us</h2>'
        .'<p>© [[copyright_year]] [[site_name]]. All rights reserved.</p><p>Technological Sound</p>'
        .'<p>Custom approved content</p></footer>';
    $english = ThemeLayout::query()->create([
        'theme_slug' => 'school-of-signals', 'region' => 'footer', 'locale' => 'en',
        'status' => 'published', 'draft_html' => $englishHtml, 'published_html' => $englishHtml,
    ]);
    $sinhala = ThemeLayout::query()->create([
        'theme_slug' => 'school-of-signals', 'region' => 'footer', 'locale' => 'si', 'status' => 'published',
        'draft_html' => $sinhalaHtml, 'published_html' => $sinhalaHtml, 'published_css' => '.custom { color: red; }',
    ]);

    $migration = require database_path('migrations/2026_10_20_120000_localize_school_footer_content.php');
    $migration->up();
    $sinhala->refresh();
    expect($sinhala->published_html)->toContain('කඩිනම් සබැඳි', 'සියලු හිමිකම් ඇවිරිණි.', 'Custom approved content')
        ->toContain('[[site_url:/si/pages/school-details-si]]', '[[site_url:/si/news]]', '[[site_url:/si/galleries]]')
        ->not->toContain('Quick Links', '[[primary_menu]]', 'Official information,')
        ->and($sinhala->draft_html)->toBe($sinhala->published_html)
        ->and($sinhala->published_css)->toBe('.custom { color: red; }')
        ->and($english->fresh()->published_html)->toBe($englishHtml)
        ->and(SiteSetting::query()->first()->site_name_si)->toBe('සංඥා පාසල')
        ->and(SiteSetting::query()->first()->site_tagline_si)->toBe('අනුමත ආදර්ශ පාඨය')
        ->and(ThemeLayoutRevision::query()->where('content_html', $sinhalaHtml)->count())->toBe(2);

    $revision = $sinhala->revision_number;
    $migration->up();
    expect($sinhala->fresh()->revision_number)->toBe($revision);

    $rendered = app(ThemeLayoutRenderer::class)->render(
        themeSlug: 'school-of-signals',
        region: ThemeLayoutRegion::Footer,
        siteSettings: SiteSetting::first(),
        primaryMenu: null,
        socialLinks: [],
        languageOptions: [],
        currentLocale: 'si',
    );
    expect($rendered['html'])->toContain('කඩිනම් සබැඳි')
        ->toContain(url('/si/pages/school-details-si'));
});

it('localizes managed navigation placeholders using the requested locale', function (): void {
    ThemeLayout::query()->create([
        'theme_slug' => 'slsc', 'region' => 'header', 'locale' => 'en', 'status' => 'published',
        'published_html' => '<header>[[mobile_menu_toggle]][[primary_menu]][[language_switcher]]</header>',
    ]);
    app()->setLocale('en');
    $rendered = app(ThemeLayoutRenderer::class)->render(
        themeSlug: 'slsc', region: ThemeLayoutRegion::Header, siteSettings: null,
        primaryMenu: null, socialLinks: [], languageOptions: [], currentLocale: 'si',
        siteName: 'School', siteTagline: '', logoUrl: null,
    );
    expect($rendered['html'])->toContain('මෙනුව විවෘත කරන්න', 'මුල් පිටුව', 'පුවත්', 'භාෂාව තෝරන්න')
        ->toContain('href="'.route('home.localized', ['locale' => 'si']).'"', 'href="'.route('galleries.index.localized', ['locale' => 'si']).'"')
        ->not->toContain('Open menu', '>Home<');
});
