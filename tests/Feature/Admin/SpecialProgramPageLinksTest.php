<?php

use App\Enums\PageStatus;
use App\Livewire\Admin\SpecialPrograms\SpecialProgramIndex;
use App\Models\Page;
use App\Models\SpecialProgram;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrator');
    $this->actingAs($admin);
    config(['app.url' => 'https://site.test/slsc_admin/public']);
    // Livewire's test update endpoint runs at the test server root.
    URL::forceRootUrl('https://site.test');
    URL::forceScheme('https');
});

it('offers page creation and only publicly available pages in the program editor', function (): void {
    $published = Page::factory()->published()->create(['title' => 'Published program information']);
    $draft = Page::factory()->create(['title' => 'Unfinished program information']);
    $scheduled = Page::factory()->published()->create(['title' => 'Future program information', 'published_at' => now()->addDay()]);
    $deleted = Page::factory()->published()->create(['title' => 'Deleted program information']);
    $deleted->delete();
    Livewire::test(SpecialProgramIndex::class)->call('startCreate')->assertSee('Create Page')
        ->assertSee('Refresh pages')->assertSee($published->title)
        ->assertDontSee($draft->title)->assertDontSee($scheduled->title)->assertDontSee($deleted->title);
});

it('links a published page from the homepage with the correct installation path', function (string $locale): void {
    $page = Page::factory()->published()->create(['slug' => 'program-details', 'locale' => $locale, 'content' => '<p>Program details page content</p>']);
    $path = ($locale === 'en' ? '' : '/'.$locale).'/pages/program-details';
    $component = Livewire::test(SpecialProgramIndex::class)->call('startCreate')
        ->set('titleEn', 'Linked program')->set('linkedPageId', (string) $page->id)
        ->assertSet('url', $path)->assertSet('openInNewTab', false)
        ->call('save')->assertHasNoErrors()->assertSet('showEditor', false);
    $program = SpecialProgram::query()->where('title_en', 'Linked program')->sole();
    $component->call('edit', $program->id)->assertSet('linkedPageId', (string) $page->id)->assertSet('url', $path);
    URL::forceRootUrl(config('app.url'));
    expect($program->url)->toBe($path)->and($program->linkUrl())->toBe('/slsc_admin/public'.$path);
    $this->get('https://site.test/')->assertOk()->assertSee('href="/slsc_admin/public'.$path.'"', false);
    $this->get('https://site.test'.$path)->assertOk()->assertSee('Program details page content');
})->with(['en', 'si']);

it('accepts copied page paths and full URLs without duplicating the base folder', function (string $input): void {
    Page::factory()->published()->create(['slug' => 'linked-page']);
    Livewire::test(SpecialProgramIndex::class)->call('startCreate')->set('titleEn', 'Manual page link')
        ->set('url', $input)->call('save')->assertHasNoErrors();
    $program = SpecialProgram::query()->where('title_en', 'Manual page link')->sole();
    URL::forceRootUrl(config('app.url'));
    expect($program->url)->toBe('/pages/linked-page')
        ->and($program->linkUrl())->toBe('/slsc_admin/public/pages/linked-page');
})->with([
    'pages/linked-page',
    '/pages/linked-page',
    '/slsc_admin/public/pages/linked-page',
    'https://site.test/slsc_admin/public/pages/linked-page',
]);

it('resolves existing relative program links without needing to save them again', function (): void {
    URL::forceRootUrl(config('app.url'));
    $program = SpecialProgram::query()->create(['title_en' => 'Existing relative link', 'url' => 'pages/legacy-program', 'category' => 'heritage', 'is_active' => true]);
    expect($program->linkUrl())->toBe('/slsc_admin/public/pages/legacy-program');
    $this->get('https://site.test/')->assertOk()->assertSee('href="/slsc_admin/public/pages/legacy-program"', false);
    expect($program->fresh()->url)->toBe('pages/legacy-program');
});

it('keeps external links independent from similarly named local pages', function (): void {
    Page::factory()->published()->create(['slug' => 'linked-page']);
    $external = 'https://external.test/pages/linked-page?view=1#details';
    $program = SpecialProgram::query()->create(['title_en' => 'External program', 'url' => $external, 'category' => 'heritage']);
    Livewire::test(SpecialProgramIndex::class)->call('edit', $program->id)
        ->assertSet('linkedPageId', '')->call('save')->assertHasNoErrors();
    expect($program->fresh()->url)->toBe($external)->and($program->linkUrl())->toBe($external);
});

it('rejects a page that becomes unpublished before saving the program', function (): void {
    $page = Page::factory()->published()->create();
    $component = Livewire::test(SpecialProgramIndex::class)->call('startCreate')->set('titleEn', 'Unavailable program')
        ->set('linkedPageId', (string) $page->id);
    $page->update(['status' => PageStatus::Draft]);
    $component->call('save')->assertHasErrors(['linkedPageId'])->assertSet('showEditor', true);
    expect(SpecialProgram::query()->where('title_en', 'Unavailable program')->exists())->toBeFalse();
});

it('explains why a manually entered draft page path cannot be opened publicly', function (): void {
    Page::factory()->create(['slug' => 'unfinished-page']);
    Livewire::test(SpecialProgramIndex::class)->call('startCreate')->set('titleEn', 'Unfinished page program')
        ->set('url', '/pages/unfinished-page')->call('save')->assertHasErrors(['url'])
        ->assertSee('Create and publish it');
});

it('rejects unsafe or ambiguous program links', function (string $input): void {
    Livewire::test(SpecialProgramIndex::class)->call('startCreate')->set('titleEn', 'Invalid program')
        ->set('url', $input)->call('save')->assertHasErrors(['url']);
    expect(SpecialProgram::query()->where('title_en', 'Invalid program')->exists())->toBeFalse();
})->with(['javascript:alert(1)', 'data:text/html,unsafe', '../pages/about', 'https://', 'https://user:pass@external.test/page']);

it('clears the selected page when a manual target replaces it', function (): void {
    $page = Page::factory()->published()->create();
    Livewire::test(SpecialProgramIndex::class)->call('startCreate')->set('titleEn', 'Manual replacement')
        ->set('linkedPageId', (string) $page->id)->set('url', '/contact')->assertSet('linkedPageId', '')
        ->call('save')->assertHasNoErrors();
    expect(SpecialProgram::query()->where('title_en', 'Manual replacement')->sole()->url)->toBe('/contact');
});
