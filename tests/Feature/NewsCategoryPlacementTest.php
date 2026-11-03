<?php

use App\Enums\NewsStatus;
use App\Livewire\Admin\News\NewsCreate;
use App\Livewire\Admin\News\NewsEdit;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    // Category IDs vary between installations. Placement must follow the slug.
    NewsCategory::query()->where('slug', 'photo-story')->update(['slug' => 'general-updates']);
    $this->photoCategory = NewsCategory::factory()->create(['slug' => 'photo-story']);
    $this->featureCategory = NewsCategory::query()->where('slug', 'news-feature')->firstOrFail();

    $this->editor = User::factory()->create();
    foreach (['admin.access', 'news.view', 'news.create', 'news.update'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $this->editor->givePermissionTo($permission);
    }
});

test('the chosen category controls both newly created language versions', function (string $type): void {
    $category = $type === 'photo_story' ? $this->photoCategory : $this->featureCategory;

    $component = Livewire::actingAs($this->editor)->test(NewsCreate::class)
        ->set('title', 'Category placement English')
        ->set('content', '<p>English article.</p>')
        ->set('sinhalaTitle', 'Category placement Sinhala')
        ->set('sinhalaContent', '<p>Sinhala article.</p>')
        ->set('categoryId', (string) $category->id)
        ->assertSet('newsType', $type);

    // A stale request value must not override the selected category on save.
    $component->instance()->newsType = $type === 'photo_story' ? 'news_feature' : 'photo_story';
    $component->call('save')->assertHasNoErrors();

    $articles = News::query()->where('title', 'like', 'Category placement%')->get();
    expect($articles)->toHaveCount(2);
    foreach ($articles as $article) {
        expect($article->category_id)->toBe($category->id)
            ->and($article->news_type)->toBe($type);
    }
})->with(['photo_story', 'news_feature']);

test('changing category moves an edited article to the matching news section', function (string $type): void {
    $target = $type === 'photo_story' ? $this->photoCategory : $this->featureCategory;
    $source = $type === 'photo_story' ? $this->featureCategory : $this->photoCategory;
    $article = News::factory()->create([
        'category_id' => $source->id,
        'news_type' => $source->newsType(),
    ]);

    Livewire::actingAs($this->editor)->test(NewsEdit::class, ['news' => $article])
        ->set('categoryId', (string) $target->id)
        ->assertSet('newsType', $type)
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->category_id)->toBe($target->id)
        ->and($article->news_type)->toBe($type)
        ->and($article->isPhotoStory())->toBe($type === 'photo_story');
})->with(['photo_story', 'news_feature']);

test('public category lists stay separate even when old type values disagree', function (): void {
    config(['awcms.active_theme' => 'slsc']);
    $photo = News::factory()->create([
        'category_id' => $this->photoCategory->id,
        'news_type' => 'news_feature',
        'title' => 'Photo Category Article',
        'status' => NewsStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    $feature = News::factory()->create([
        'category_id' => 2,
        'news_type' => 'photo_story',
        'title' => 'General Category Article',
        'status' => NewsStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    News::factory()->create([
        'category_id' => $this->photoCategory->id,
        'title' => 'Unpublished Photo Article',
    ]);

    expect($photo->isPhotoStory())->toBeTrue()
        ->and($feature->isPhotoStory())->toBeFalse();

    $this->get(route('news.index', ['type' => 'photo-story']))
        ->assertOk()->assertSee('Photo Category Article')
        ->assertDontSee('General Category Article')->assertDontSee('Unpublished Photo Article')
        ->assertSee('aria-current="page"', false);
    $this->get(route('news.index', ['type' => 'news-feature']))
        ->assertOk()->assertSee('General Category Article')->assertDontSee('Photo Category Article');
});

test('pagination and language links keep the selected news category', function (): void {
    config(['awcms.active_theme' => 'slsc']);
    News::factory()->count(13)->create([
        'category_id' => $this->photoCategory->id,
        'status' => NewsStatus::Published,
        'published_at' => now()->subHour(),
    ]);

    $response = $this->get(route('news.index', ['type' => 'photo-story']));
    $response->assertOk()->assertSee('school-news-pager')
        ->assertSee('type=photo-story&amp;page=2', false);
    foreach ($response->viewData('languageVersions') as $version) {
        expect($version['url'])->toContain('type=photo-story');
    }
});

test('homepage sections never borrow articles from another category or use sample news', function (): void {
    config(['awcms.active_theme' => 'slsc']);
    News::factory()->count(2)->published()->create(['category_id' => $this->photoCategory->id]);

    $response = $this->get(route('home'))->assertOk();
    expect($response->viewData('photoStory'))->not->toBeNull()
        ->and($response->viewData('newsFeatures'))->toBeEmpty();
    $response->assertSee('No news features have been published yet.')
        ->assertDontSee('Supporting Signal Corps personnel, professional achievements');

    News::query()->update(['category_id' => $this->featureCategory->id]);
    $response = $this->get(route('home'))->assertOk();
    expect($response->viewData('photoStory'))->toBeNull()
        ->and($response->viewData('newsFeatures'))->toHaveCount(2);
    $response->assertSee('No photo stories have been published yet.');
});

test('Featured publication option prioritises articles inside their own homepage category', function (): void {
    config(['awcms.active_theme' => 'slsc']);
    foreach ([$this->photoCategory, $this->featureCategory] as $category) {
        News::factory()->published()->create(['category_id' => $category->id, 'published_at' => now()->subHour()]);
        News::factory()->published()->create(['category_id' => $category->id, 'is_featured' => true, 'published_at' => now()->subDays(2)]);
    }

    $response = $this->get(route('home'))->assertOk();
    expect($response->viewData('photoStory')->is_featured)->toBeTrue()
        ->and($response->viewData('newsFeatures')->first()->is_featured)->toBeTrue();
});
