<?php

use App\Models\Course;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\MediaAsset;
use App\Models\News;
use App\Models\Publication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createSearchDocument(array $attributes = [], array $mediaAttributes = []): Document
{
    $document = Document::factory()->published()->create(['current_version' => 1, ...$attributes]);
    $media = MediaAsset::factory()->document()->create([
        'stored_name' => $document->uuid.'.pdf',
        'path' => 'media/testing/'.$document->uuid.'.pdf',
        ...$mediaAttributes,
    ]);
    Storage::disk($media->disk)->put($media->path, "%PDF-1.4\n%%EOF\n");
    DocumentVersion::query()->create(['document_id' => $document->id, 'media_asset_id' => $media->id, 'version' => 1]);

    return $document;
}

beforeEach(function (): void {
    config()->set('awcms.active_theme', 'slsc');
});

it('loads search page successfully in English and Sinhala', function (): void {
    $this->get(route('search'))->assertOk()->assertSee('Search');
    $this->get(route('search.localized', ['locale' => 'si']))->assertOk()->assertSee('සොයන්න');
});

it('finds published news, courses, documents and publications by keyword', function (): void {
    News::factory()->published()->create([
        'title' => 'Signal Officers Annual Conference',
        'summary' => 'Annual conference of signal officers held in Colombo.',
        'locale' => 'en',
    ]);

    Course::query()->create([
        'name' => 'Signal Young Officers Course',
        'name_si' => 'සංඥා තරුණ නිලධාරී පාඨමාලාව',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    createSearchDocument([
        'title' => 'Signal Corps Training Guidelines 2026',
    ]);

    Storage::disk('local')->put('publications/search.pdf', "%PDF-1.4\n%%EOF\n");
    Storage::disk('local')->put('publications/search.jpg', 'test cover');
    Publication::query()->create([
        'title' => 'Signal Corps Journal',
        'disk' => 'local',
        'pdf_path' => 'publications/search.pdf',
        'cover_path' => 'publications/search.jpg',
        'original_name' => 'search.pdf',
        'size_bytes' => 15,
        'cover_width' => 600,
        'cover_height' => 800,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('search', ['q' => 'Signal']))->assertOk();
    $response->assertSeeText('Signal Officers Annual Conference');
    $response->assertSeeText('Signal Young Officers Course');
    $response->assertSeeText('Signal Corps Training Guidelines 2026');
    $response->assertSeeText('Signal Corps Journal');
});

it('only exposes currently published documents in search and suggestions', function (): void {
    $published = createSearchDocument(['title' => 'SearchVisibility Public Document', 'title_si' => 'SearchVisibility Sinhala Document']);
    createSearchDocument(['title' => 'SearchVisibility Draft Document', 'status' => 'draft', 'published_at' => null]);
    createSearchDocument(['title' => 'SearchVisibility Future Document', 'published_at' => now()->addDay()]);
    createSearchDocument(['title' => 'SearchVisibility Archived Document', 'status' => 'archived']);
    createSearchDocument(['title' => 'SearchVisibility Deleted Document'])->delete();

    $response = $this->get(route('search', ['q' => 'SearchVisibility', 'type' => 'documents']))->assertOk();
    $response->assertSeeText('SearchVisibility Public Document')->assertSee(route('documents.show', ['slug' => $published->slug]), false);
    foreach (['Draft', 'Future', 'Archived', 'Deleted'] as $status) {
        $response->assertDontSeeText('SearchVisibility '.$status.' Document');
    }

    $suggestions = $this->getJson('/search/suggest?q=SearchVisibility')->assertOk();
    expect(array_column($suggestions->json('results'), 'title'))->toBe(['SearchVisibility Public Document']);
    $localized = $this->getJson('/si/search/suggest?q=SearchVisibility')->assertOk();
    expect($localized->json('results.0.title'))->toBe('SearchVisibility Sinhala Document')
        ->and($localized->json('results.0.url'))->toBe(route('documents.show.localized', ['locale' => 'si', 'slug' => $published->slug]));
});

it('hides documents with a private missing or deleted current file from search and suggestions', function (): void {
    createSearchDocument(['title' => 'RestrictedSearch Private Document'], ['visibility' => 'internal']);
    $replaced = createSearchDocument(['title' => 'RestrictedSearch Older Public File']);
    $private = MediaAsset::factory()->document()->create(['visibility' => 'internal']);
    DocumentVersion::query()->create(['document_id' => $replaced->id, 'media_asset_id' => $private->id, 'version' => 2]);
    $replaced->update(['current_version' => 2]);
    $deleted = createSearchDocument(['title' => 'RestrictedSearch Deleted File']);
    $deleted->versions()->firstOrFail()->media->delete();
    Document::factory()->published()->create(['title' => 'RestrictedSearch Missing Version']);

    foreach (['/search?q=RestrictedSearch', '/si/search?q=RestrictedSearch'] as $path) {
        $this->get($path)->assertOk()->assertViewHas('results', fn ($results) => $results->isEmpty());
    }
    foreach (['/search/suggest?q=RestrictedSearch', '/si/search/suggest?q=RestrictedSearch'] as $path) {
        $this->getJson($path)->assertOk()->assertJsonPath('results', []);
    }
});

it('returns an empty suggestion state for a short query and escapes HTML in the search page', function (): void {
    $this->getJson('/search/suggest?q=S')->assertOk()->assertJsonPath('total', 0)->assertJsonPath('results', []);
    $query = '<script>alert(1)</script>';
    $this->get(route('search', ['q' => $query]))->assertOk()->assertSee($query)->assertDontSee($query, false);
});

it('rejects structured search parameters instead of emitting PHP warnings', function (string $path, string $parameter): void {
    $this->getJson($path.'?'.http_build_query([$parameter => ['invalid']]))
        ->assertUnprocessable()->assertJsonValidationErrors($parameter);
})->with([
    'search query' => ['/search', 'q'],
    'suggestion query' => ['/search/suggest', 'q'],
    'search category' => ['/search', 'type'],
]);

it('filters search results by category tab', function (): void {
    News::factory()->published()->create([
        'title' => 'Technology Seminar 2026',
        'locale' => 'en',
    ]);

    Course::query()->create([
        'name' => 'Technology Sound Foundation Course',
        'is_active' => true,
    ]);

    // Filter by news only
    $newsResponse = $this->get(route('search', ['q' => 'Technology', 'type' => 'news']))->assertOk();
    $newsResponse->assertSeeText('Technology Seminar 2026');
    $newsResponse->assertDontSeeText('Technology Sound Foundation Course');

    // Filter by courses only
    $courseResponse = $this->get(route('search', ['q' => 'Technology', 'type' => 'courses']))->assertOk();
    $courseResponse->assertSeeText('Technology Sound Foundation Course');
    $courseResponse->assertDontSeeText('Technology Seminar 2026');
});

it('does not display unpublished or draft records in search results', function (): void {
    News::factory()->create([
        'title' => 'Secret Draft Project',
        'status' => 'draft',
        'published_at' => null,
        'locale' => 'en',
    ]);

    Course::query()->create([
        'name' => 'Inactive Signal Training',
        'is_active' => false,
    ]);

    $response = $this->get(route('search', ['q' => 'Secret']))->assertOk();
    $response->assertDontSeeText('Secret Draft Project');

    $inactiveResponse = $this->get(route('search', ['q' => 'Inactive']))->assertOk();
    $inactiveResponse->assertDontSeeText('Inactive Signal Training');
});
