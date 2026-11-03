<?php

use App\Livewire\Admin\News\NewsCreate;
use App\Livewire\Admin\News\NewsEdit;
use App\Models\News;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::fake('public');
    $user = User::factory()->create();
    foreach (['news.create', 'news.update', 'media.upload'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }
    $this->actingAs($user);
});

it('provides upload progress and clear preparation controls on news forms', function (string $mode): void {
    $component = $mode === 'edit'
        ? Livewire::test(NewsEdit::class, ['news' => News::factory()->create()])
        : Livewire::test(NewsCreate::class);

    $component->assertSee('awcmsNewsImageUpload($wire)', false)
        ->assertSee('x-on:change.capture="validateSelection($event)"', false)
        ->assertSee('x-on:submit.capture="guardSubmit($event)"', false)
        ->assertSee('Selected images upload progress')
        ->assertSee('Cancel upload')
        ->assertSee('news-gallery-upload-error', false)
        ->assertDontSee('wire:loading wire:target="galleryUploads"', false);

    $fixture = storage_path('framework/testing/news-upload-'.$mode.'.html');
    if (getenv('NEWS_UPLOAD_WRITE_FIXTURES') === '1') {
        file_put_contents($fixture, $component->html());
    }

    $component->set('galleryUploads', [UploadedFile::fake()->image('training.jpg', 80, 60)])
        ->assertHasNoErrors()
        ->assertSee('training.jpg')
        ->assertDontSee('window\\.Sortable', false)
        ->assertSee($mode === 'edit'
            ? 'Images are ready. Click Upload Images to attach them.'
            : 'Images are ready. Save the draft below to keep them.');
})->with(['create', 'edit']);
