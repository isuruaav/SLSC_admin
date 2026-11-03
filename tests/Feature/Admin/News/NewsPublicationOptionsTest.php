<?php

use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Enums\NewsStatus;
use App\Livewire\Admin\News\NewsCreate;
use App\Livewire\Admin\News\NewsEdit;
use App\Models\MediaAsset;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsImage;
use App\Models\User;
use App\Services\PublicGalleryFeed;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    $this->editor = User::factory()->create();
    foreach (['news.create', 'news.update', 'news.view', 'news.publish'] as $permission) {
        $this->editor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->photo = NewsCategory::query()->where('slug', 'photo-story')->firstOrFail();
    $this->feature = NewsCategory::query()->where('slug', 'news-feature')->firstOrFail();
});

function publicationFormElement(string $html, string $id): DOMElement
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $element = (new DOMXPath($document))->query('//*[@id="'.$id.'"]')->item(0);
    expect($element)->toBeInstanceOf(DOMElement::class);

    return $element;
}

it('binds the visible article type radios to the category that is actually saved', function (string $mode): void {
    $article = News::factory()->create(['category_id' => $this->feature->id]);
    $component = Livewire::actingAs($this->editor)->test(
        $mode === 'create' ? NewsCreate::class : NewsEdit::class,
        $mode === 'create' ? [] : ['news' => $article],
    );

    $document = new DOMDocument;
    @$document->loadHTML($component->html());
    $radios = (new DOMXPath($document))->query('//input[@type="radio" and @name="categoryId"]');
    expect($radios->length)->toBe(2);
    $photoRadio = null;
    foreach ($radios as $radio) {
        if ($radio->getAttribute('value') === (string) $this->photo->id) {
            $photoRadio = $radio;
        }
    }
    expect($photoRadio)->toBeInstanceOf(DOMElement::class)
        ->and($photoRadio->getAttribute('wire:model.live'))->toBe('categoryId');

    $component->set($photoRadio->getAttribute('wire:model.live'), $photoRadio->getAttribute('value'))
        ->assertSet('categoryId', (string) $this->photo->id)
        ->assertSet('newsType', 'photo_story')
        ->assertDontSeeHtml('id="news-category"')
        ->set('title', 'Article from selected radio')
        ->set('content', '<p>Article content.</p>')
        ->call('save')->assertHasNoErrors();

    $saved = News::query()->where('title', 'Article from selected radio')->sole();
    expect($saved->category_id)->toBe($this->photo->id)->and($saved->news_type)->toBe('photo_story');
})->with(['create', 'edit']);

it('keeps the Featured publication option separate from the selected gallery cover', function (): void {
    $article = News::factory()->create([
        'category_id' => $this->photo->id, 'is_featured' => true, 'show_in_gallery' => true,
    ]);
    $images = MediaAsset::factory()->count(2)->create(['type' => MediaType::Image, 'visibility' => MediaVisibility::Public]);
    foreach ($images as $index => $image) {
        NewsImage::query()->create(['news_id' => $article->id, 'media_asset_id' => $image->id, 'sort_order' => $index]);
    }
    $article->update(['featured_image_id' => $images[0]->id]);

    $component = Livewire::actingAs($this->editor)->test(NewsEdit::class, ['news' => $article]);
    expect(publicationFormElement($component->html(), 'news-option-featured')->hasAttribute('checked'))->toBeTrue()
        ->and(publicationFormElement($component->html(), 'news-option-gallery')->hasAttribute('checked'))->toBeTrue();

    $component->set('categoryId', (string) $this->feature->id)->call('save')->assertHasNoErrors();
    expect($article->refresh()->is_featured)->toBeTrue()->and($article->show_in_gallery)->toBeTrue();

    $component->set('isFeatured', false)->set('showInGallery', false)->call('save')->assertHasNoErrors();
    expect($article->refresh()->is_featured)->toBeFalse()->and($article->show_in_gallery)->toBeFalse();
    expect(publicationFormElement($component->html(), 'news-option-featured')->hasAttribute('checked'))->toBeFalse()
        ->and(publicationFormElement($component->html(), 'news-option-gallery')->hasAttribute('checked'))->toBeFalse();
});

it('saves options and the selected category for both languages before publishing', function (): void {
    $english = News::factory()->create(['category_id' => $this->feature->id]);
    $sinhala = News::factory()->create([
        'category_id' => $this->feature->id, 'locale' => 'si', 'translation_group' => $english->translation_group,
    ]);

    Livewire::actingAs($this->editor)->test(NewsEdit::class, ['news' => $sinhala])
        ->assertSee('Save &amp; Publish', false)
        ->set('categoryId', (string) $this->photo->id)
        ->set('isFeatured', true)->set('showInGallery', true)
        ->call('saveAndPublish')->assertHasNoErrors();

    foreach ([$english, $sinhala] as $article) {
        expect($article->refresh()->status)->toBe(NewsStatus::Published)
            ->and($article->category_id)->toBe($this->photo->id)
            ->and($article->news_type)->toBe('photo_story')
            ->and($article->is_featured)->toBeTrue()->and($article->show_in_gallery)->toBeTrue();
    }
    $this->get(route('news.index', ['type' => 'photo-story']))->assertOk()->assertSee($english->title);
    $this->get(route('news.index', ['type' => 'news-feature']))->assertOk()->assertDontSee($english->title);
});

it('uses the selected publication time and keeps scheduled news private until that time', function (string $mode): void {
    $future = now()->addHours(2)->startOfMinute();
    $article = News::factory()->create(['category_id' => $this->feature->id]);
    $component = Livewire::actingAs($this->editor)->test(
        $mode === 'create' ? NewsCreate::class : NewsEdit::class,
        $mode === 'create' ? [] : ['news' => $article],
    );
    $component->set('title', 'Scheduled publication test')
        ->set('content', '<p>Scheduled body.</p>')
        ->set('publishedAt', $future->format('Y-m-d\TH:i'))
        ->set('isFeatured', true)->set('showInGallery', true)
        ->call('saveAndPublish')->assertHasNoErrors();

    $saved = News::query()->where('title', 'Scheduled publication test')->sole();
    expect($saved->status)->toBe(NewsStatus::Published)
        ->and($saved->published_at->equalTo($future))->toBeTrue();
    $this->get(route('news.index'))->assertOk()->assertDontSee('Scheduled publication test');
    $this->travel(3)->hours();
    $this->get(route('news.index'))->assertOk()->assertSee('Scheduled publication test');
})->with(['create', 'edit']);

it('keeps draft changes private and publishes public images only when Gallery is enabled', function (): void {
    $article = News::factory()->create(['category_id' => $this->feature->id]);
    $image = MediaAsset::factory()->create(['type' => MediaType::Image, 'visibility' => MediaVisibility::Public]);
    NewsImage::query()->create(['news_id' => $article->id, 'media_asset_id' => $image->id, 'sort_order' => 0]);

    $component = Livewire::actingAs($this->editor)->test(NewsEdit::class, ['news' => $article])
        ->set('isFeatured', true)->set('showInGallery', true)->call('save')->assertHasNoErrors();
    expect($article->refresh()->status)->toBe(NewsStatus::Draft)
        ->and(app(PublicGalleryFeed::class)->news('en')->whereKey($article->id)->exists())->toBeFalse();

    $component->call('saveAndPublish')->assertHasNoErrors();
    expect(app(PublicGalleryFeed::class)->news('en')->whereKey($article->id)->exists())->toBeTrue();
});

it('rejects Save and Publish without publication permission before saving any changes', function (): void {
    $article = News::factory()->create(['category_id' => $this->feature->id, 'is_featured' => false]);
    $this->editor->revokePermissionTo('news.publish');

    Livewire::actingAs($this->editor)->test(NewsEdit::class, ['news' => $article])
        ->assertDontSeeHtml('wire:click="saveAndPublish"')
        ->set('categoryId', (string) $this->photo->id)->set('isFeatured', true)
        ->call('saveAndPublish')->assertForbidden();

    expect($article->refresh()->category_id)->toBe($this->feature->id)
        ->and($article->status)->toBe(NewsStatus::Draft)->and($article->is_featured)->toBeFalse();
});
