<?php

use App\Enums\NewsLocale;
use App\Enums\NewsStatus;
use App\Livewire\Admin\News\NewsEdit;
use App\Livewire\Admin\News\NewsIndex;
use App\Models\AuditLog;
use App\Models\News;
use App\Models\NewsRevision;
use App\Models\User;
use App\Services\NewsWorkflowService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Site Administrator');
});

/** @return array{News, News} */
function newsUnpublishPair(NewsStatus $englishStatus = NewsStatus::Published, NewsStatus $sinhalaStatus = NewsStatus::Published): array
{
    $english = News::factory()->create([
        'title' => 'English before editing',
        'locale' => NewsLocale::English,
        'status' => $englishStatus,
        'published_at' => $englishStatus === NewsStatus::Published ? now()->subDay() : null,
    ]);
    $sinhala = News::factory()->create([
        'title' => 'Sinhala before editing',
        'locale' => NewsLocale::Sinhala,
        'translation_group' => $english->translation_group,
        'category_id' => $english->category_id,
        'status' => $sinhalaStatus,
        'published_at' => $sinhalaStatus === NewsStatus::Published ? now()->subDay() : null,
    ]);

    return [$english, $sinhala];
}

it('unpublishes both editor languages from either list row and then saves their edits', function (int $selectedIndex): void {
    [$english, $sinhala] = $pair = newsUnpublishPair();
    Livewire::actingAs($this->administrator)->test(NewsIndex::class)
        ->call('unpublishArticle', $pair[$selectedIndex]->id)
        ->assertHasNoErrors()
        ->assertSeeText('2 language versions were unpublished and returned to Draft.');

    foreach ($pair as $article) {
        expect($article->refresh()->status)->toBe(NewsStatus::Draft)
            ->and($article->published_at)->toBeNull()
            ->and($article->published_by)->toBeNull();
        $revision = NewsRevision::query()->where('news_id', $article->id)->sole();
        expect($revision->status)->toBe(NewsStatus::Published);
    }
    expect(AuditLog::query()->where('event', 'news.unpublished')->count())->toBe(2);

    Livewire::actingAs($this->administrator)->test(NewsEdit::class, ['news' => $pair[$selectedIndex]])
        ->assertSet('bilingual', true)
        ->assertViewHas('editable', true)
        ->assertDontSee('Editing locked')
        ->set('title', 'Edited English article')
        ->set('sinhalaTitle', 'Edited Sinhala article')
        ->call('save')->assertHasNoErrors();

    expect($english->refresh()->title)->toBe('Edited English article')
        ->and($sinhala->refresh()->title)->toBe('Edited Sinhala article');
})->with(['English row' => 0, 'Sinhala row' => 1]);

it('recovers the existing mixed Draft and Published state from the editor', function (string $draftLocale): void {
    [$english, $sinhala] = newsUnpublishPair(
        $draftLocale === 'en' ? NewsStatus::Draft : NewsStatus::Published,
        $draftLocale === 'si' ? NewsStatus::Draft : NewsStatus::Published,
    );
    $selected = $draftLocale === 'en' ? $english : $sinhala;

    Livewire::actingAs($this->administrator)->test(NewsEdit::class, ['news' => $selected])
        ->assertViewHas('editable', false)
        ->assertSeeText('Both versions must be Draft before editing.')
        ->assertSee('Unpublish and edit')
        ->call('unpublishForEditing')->assertHasNoErrors()
        ->assertViewHas('editable', true)
        ->assertSet('publishedAt', '')
        ->assertDontSee('Editing locked')
        ->set('title', 'Recovered English draft')
        ->call('save')->assertHasNoErrors();

    expect($english->refresh()->status)->toBe(NewsStatus::Draft)
        ->and($sinhala->refresh()->status)->toBe(NewsStatus::Draft)
        ->and($english->title)->toBe('Recovered English draft');
    expect(NewsRevision::query()->where('reason', 'Snapshot before unpublishing.')->count())->toBe(1);
})->with(['en', 'si']);

it('leaves other translation groups and Tamil publication unchanged when unpublishing the pair', function (): void {
    [$english, $sinhala] = newsUnpublishPair();
    $tamil = News::factory()->published()->create([
        'locale' => NewsLocale::Tamil, 'translation_group' => $english->translation_group,
    ]);
    $unrelated = News::factory()->published()->create();

    app(NewsWorkflowService::class)->unpublishForEditing($english, $this->administrator);

    expect($english->refresh()->status)->toBe(NewsStatus::Draft)
        ->and($sinhala->refresh()->status)->toBe(NewsStatus::Draft)
        ->and($tamil->refresh()->status)->toBe(NewsStatus::Published)
        ->and($unrelated->refresh()->status)->toBe(NewsStatus::Published);
});

it('unpublishes a Tamil article independently of its English and Sinhala translations', function (): void {
    [$english, $sinhala] = newsUnpublishPair();
    $tamil = News::factory()->published()->create([
        'locale' => NewsLocale::Tamil, 'translation_group' => $english->translation_group,
    ]);

    app(NewsWorkflowService::class)->unpublishForEditing($tamil, $this->administrator);

    expect($tamil->refresh()->status)->toBe(NewsStatus::Draft)
        ->and($english->refresh()->status)->toBe(NewsStatus::Published)
        ->and($sinhala->refresh()->status)->toBe(NewsStatus::Published);
});

it('skips trashed translations and repeated unpublish requests', function (): void {
    [$english, $sinhala] = newsUnpublishPair();
    $sinhala->delete();
    $service = app(NewsWorkflowService::class);
    expect($service->unpublishForEditing($english, $this->administrator))->toBe(1)
        ->and($service->unpublishForEditing($english, $this->administrator))->toBe(0);

    expect(News::withTrashed()->findOrFail($sinhala->id)->status)->toBe(NewsStatus::Published)
        ->and(NewsRevision::query()->where('news_id', $english->id)->count())->toBe(1);
});

it('keeps published content locked for editors without publishing permission', function (): void {
    [$english, $sinhala] = newsUnpublishPair(NewsStatus::Draft);
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    Livewire::actingAs($editor)->test(NewsEdit::class, ['news' => $english])
        ->assertDontSee('Unpublish and edit')
        ->assertSeeText('Ask a user with publishing permission')
        ->call('unpublishForEditing')->assertForbidden();
    Livewire::actingAs($editor)->test(NewsIndex::class)
        ->call('unpublishArticle', $sinhala->id)->assertForbidden();
    Livewire::actingAs($editor)->test(NewsEdit::class, ['news' => $english])
        ->set('sinhalaTitle', 'Unauthorized change')
        ->call('save')->assertHasErrors('news');

    expect($sinhala->refresh()->status)->toBe(NewsStatus::Published)
        ->and($sinhala->title)->toBe('Sinhala before editing')
        ->and(NewsRevision::query()->count())->toBe(0);
});

it('does not bypass other workflow states while unpublishing published versions', function (): void {
    [$english, $sinhala] = newsUnpublishPair(NewsStatus::Published, NewsStatus::Submitted);

    Livewire::actingAs($this->administrator)->test(NewsEdit::class, ['news' => $english])
        ->call('unpublishForEditing')->assertHasNoErrors()
        ->assertViewHas('editable', false)
        ->assertSee('Submitted for Review')
        ->assertDontSee('Unpublish and edit')
        ->call('save')->assertHasErrors('news');

    expect($english->refresh()->status)->toBe(NewsStatus::Draft)
        ->and($sinhala->refresh()->status)->toBe(NewsStatus::Submitted);
});

it('rolls back both versions and revision history if either unpublish fails', function (): void {
    [$english, $sinhala] = newsUnpublishPair();
    $dispatcher = News::getEventDispatcher();
    News::setEventDispatcher(clone $dispatcher);
    News::updating(function (News $article) use ($sinhala): void {
        if ($article->id === $sinhala->id && $article->status === NewsStatus::Draft) {
            throw new RuntimeException('Simulated second-language save failure');
        }
    });

    try {
        expect(fn () => app(NewsWorkflowService::class)->unpublishForEditing($english, $this->administrator))
            ->toThrow(RuntimeException::class, 'Simulated second-language save failure');
    } finally {
        News::setEventDispatcher($dispatcher);
    }

    expect($english->refresh()->status)->toBe(NewsStatus::Published)
        ->and($sinhala->refresh()->status)->toBe(NewsStatus::Published)
        ->and(NewsRevision::query()->count())->toBe(0)
        ->and(AuditLog::query()->where('event', 'news.unpublished')->count())->toBe(0);
});
