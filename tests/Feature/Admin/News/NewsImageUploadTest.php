<?php

use App\Livewire\Admin\News\NewsEdit;
use App\Models\MediaAsset;
use App\Models\News;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::fake('public');

    $this->editor = User::factory()->create();
    foreach (['news.update', 'media.upload'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $this->editor->givePermissionTo($permission);
    }
    $this->actingAs($this->editor);
});

it('previews and saves news images through valid Livewire JSON responses', function (string $locale, string $title, string $filename): void {
    $news = News::factory()->create(['locale' => $locale, 'title' => $title]);

    Livewire::test(NewsEdit::class, ['news' => $news])
        ->set('galleryUploads', [UploadedFile::fake()->image($filename, 80, 60)])
        ->assertHasNoErrors()
        ->assertSee($filename)
        ->call('uploadGalleryImages')
        ->assertHasNoErrors()
        ->assertSet('galleryUploads', []);

    $media = MediaAsset::query()->sole();
    expect($news->images()->count())->toBe(1)
        ->and($media->variants()->count())->toBe(2);
    Storage::disk('public')->assertExists($media->path);
})->with([
    'English image' => ['en', 'Training activity', 'training.jpg'],
    'Sinhala image' => ['si', 'පුහුණු වැඩසටහන', 'පුහුණුව.jpg'],
    'Tamil image' => ['ta', 'பயிற்சி நிகழ்ச்சி', 'பயிற்சி.jpg'],
]);

it('returns a friendly validation error for uppercase news image filenames', function (): void {
    $news = News::factory()->create();

    Livewire::test(NewsEdit::class, ['news' => $news])
        ->set('galleryUploads', [UploadedFile::fake()->image('Training.jpg', 80, 60)])
        ->call('uploadGalleryImages')
        ->assertHasErrors('galleryUploads')
        ->assertSee('contains capital letters');

    expect(MediaAsset::query()->count())->toBe(0);
});

it('keeps a news image upload usable when the hosting render contains malformed UTF-8', function (): void {
    $news = News::factory()->create(['title' => 'පුහුණු වැඩසටහන']);
    $component = Livewire::test(NewsEdit::class, ['news' => $news]);
    Log::spy();

    // Model a hosting view/cache containing an invalid byte. The initial page
    // can display that byte, but encoding the subsequent HTML as JSON fails.
    $stop = Livewire::listen('render', static function ($instance) {
        // Only inject the invalid byte after images have been saved.
        if (! $instance instanceof NewsEdit || ! News::query()->find($instance->newsId)?->images()->exists()) {
            return null;
        }

        return static fn (string $html): string => $html."<!-- legacy byte: \x96 -->";
    });

    try {
        $component->set('galleryUploads', [UploadedFile::fake()->image('training.jpg', 80, 60)])
            ->assertHasNoErrors()
            ->assertSet('title', 'පුහුණු වැඩසටහන')
            ->call('uploadGalleryImages')
            ->assertHasNoErrors()
            ->assertSet('galleryUploads', []);
    } finally {
        $stop();
    }

    expect($news->images()->count())->toBe(1);
    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => $message === 'Repaired malformed UTF-8 in Livewire output.'
            && $context['components'] === ['admin.news.news-edit']
            && ! array_key_exists('html', $context),
    );
});
