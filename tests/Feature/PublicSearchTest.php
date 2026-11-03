<?php

use App\Models\Course;
use App\Models\Document;
use App\Models\News;
use App\Models\Page;
use App\Models\Publication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

    Document::factory()->published()->create([
        'title' => 'Signal Corps Training Guidelines 2026',
        'locale' => 'en',
    ]);

    $response = $this->get(route('search', ['q' => 'Signal']))->assertOk();
    $response->assertSeeText('Signal Officers Annual Conference');
    $response->assertSeeText('Signal Young Officers Course');
    $response->assertSeeText('Signal Corps Training Guidelines 2026');
});

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

