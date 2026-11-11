<?php

use App\Models\News;
use App\Models\Page;
use App\Models\User;
use App\Services\LeadershipProfileContent;
use App\Services\SiteUrlService;
use App\Services\ThemeLayoutRenderer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    config(['app.url' => 'https://site.test/slsc_admin/public']);
    URL::forceRootUrl(config('app.url'));
    URL::forceScheme('https');
});

it('shows and copies only the portable path for uploaded file types', function (string $filename): void {
    $url = 'https://site.test/slsc_admin/public/media-files/'.$filename;
    $rendered = Blade::render('<x-admin.copy-path :path="$url" />', ['url' => $url]);

    expect($rendered)->toContain('value="/media-files/'.$filename.'"')
        ->not->toContain('https://site.test')
        ->not->toContain('/slsc_admin/public');
})->with(['image.jpg', 'report.docx', 'notice.pdf', 'clip.mp4']);

it('preserves encoded filenames and URL suffixes when shortening local links', function (): void {
    $service = app(SiteUrlService::class);
    expect($service->portable('https://site.test/slsc_admin/public/media-files/Annual%20Report.pdf?v=2#page=3'))
        ->toBe('/media-files/Annual%20Report.pdf?v=2#page=3')
        ->and($service->portable('/slsc_admin/public/document-files/en/guide/view'))
        ->toBe('/document-files/en/guide/view');
});

it('keeps external and unrelated URLs intact', function (): void {
    $service = app(SiteUrlService::class);
    foreach (['https://cdn.test/image.jpg', '//cdn.test/image.jpg', 'https://site.test.evil.test/image.jpg', 'https://site.test:8443/image.jpg', 'https://site.test/other-app/image.jpg', 'https://user:pass@site.test/slsc_admin/public/image.jpg', '#section', '?page=2', 'mailto:editor@example.test'] as $value) {
        expect($service->portable($value))->toBe($value);
    }
});

it('resolves file and page references once without changing external URLs or text', function (): void {
    $service = app(SiteUrlService::class);
    $html = '<img src="/media-files/photo.jpg" data-src="/unchanged.jpg" title="keep src=\'/media-files/title.jpg\' and &gt; text">'
        .'<a href="/document-files/en/guide/view?x=1&amp;y=2">Guide</a>'
        .'<a href="/pages/about">About</a><video src="/media-files/clip.mp4" poster="/media-files/poster.jpg"></video>'
        .'<img src="https://cdn.test/photo.jpg"><a href="#section">Jump</a>'
        .'<p>src="/media-files/text.jpg"</p>';
    $resolved = $service->resolveHtml($html);

    expect($resolved)->toContain('src="/slsc_admin/public/media-files/photo.jpg"')
        ->toContain('href="/slsc_admin/public/document-files/en/guide/view?x=1&amp;y=2"')
        ->toContain('href="/slsc_admin/public/pages/about"')
        ->toContain('poster="/slsc_admin/public/media-files/poster.jpg"')
        ->toContain('data-src="/unchanged.jpg"')
        ->toContain('title="keep src=\'/media-files/title.jpg\' and &gt; text"')
        ->toContain('src="https://cdn.test/photo.jpg"')
        ->toContain('href="#section"')
        ->toContain('<p>src="/media-files/text.jpg"</p>')
        ->and($service->resolveHtml($resolved))->toBe($resolved);
});

it('supports installations directly on a domain', function (): void {
    URL::forceRootUrl('https://site.test');
    config(['app.url' => 'https://site.test']);
    $service = app(SiteUrlService::class);
    expect($service->portable('https://site.test/media-files/photo.jpg'))->toBe('/media-files/photo.jpg')
        ->and($service->resolve('/media-files/photo.jpg'))->toBe('/media-files/photo.jpg');
});

it('renders portable paths in public content and administrator previews', function (string $type): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrator');
    $markup = '<img src="/media-files/portable-photo.jpg" alt="Photo">'
        .'<a href="/document-files/en/portable-guide/view">Guide</a>';
    $record = $type === 'pages'
        ? Page::factory()->published()->create(['content' => $markup])
        : News::factory()->published()->create(['content' => $markup]);
    $storedContent = $record->fresh()->content;

    foreach (['/'.$type.'/'.$record->slug, '/admin/'.$type.'/'.$record->id.'/preview'] as $path) {
        $this->actingAs($admin)->get('https://site.test'.$path)->assertOk()
            ->assertSee('src="/slsc_admin/public/media-files/portable-photo.jpg"', false)
            ->assertSee('href="/slsc_admin/public/document-files/en/portable-guide/view"', false);
    }
    expect($record->fresh()->content)->toBe($storedContent)
        ->toContain('src="/media-files/portable-photo.jpg"');
})->with(['pages', 'news']);

it('resolves short paths in leadership profiles and shared theme layouts', function (): void {
    $html = '<img src="/media-files/portrait.jpg" alt="Portrait"><a href="/pages/profile">Profile</a>';
    $profile = app(LeadershipProfileContent::class)->document($html, '', '', 'en');
    $layout = app(ThemeLayoutRenderer::class)->renderSource('slsc', $html, '');

    foreach ([$profile, $layout['html']] as $rendered) {
        expect($rendered)->toContain('src="/slsc_admin/public/media-files/portrait.jpg"')
            ->toContain('href="/slsc_admin/public/pages/profile"');
    }
});
