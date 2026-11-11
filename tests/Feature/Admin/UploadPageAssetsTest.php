<?php

use App\Enums\MediaVisibility;
use App\Livewire\Admin\Documents\DocumentUploads;
use App\Livewire\Admin\Media\MediaEdit;
use App\Livewire\Admin\Media\MediaIndex;
use App\Livewire\Admin\Media\MediaUpload;
use App\Models\Document;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\User;
use App\Services\MediaUrlService;
use App\Services\SiteUrlService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
    Storage::fake('local');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Administrator');
});

it('provides uploaded file paths that survive page publishing', function (string $type, string $mode): void {
    $file = $type === 'image'
        ? UploadedFile::fake()->image('training.jpg', 100, 80)
        : UploadedFile::fake()->createWithContent('notice.pdf', "%PDF-1.4\n%%EOF\n");

    $upload = Livewire::actingAs($this->admin)
        ->test(MediaUpload::class)
        ->set('file', $file)
        ->set('type', $type)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Copy Path');

    $media = MediaAsset::query()->sole();
    $path = app(SiteUrlService::class)->portable(app(MediaUrlService::class)->namedOriginal($media));
    expect($path)->toEndWith('/'.$file->getClientOriginalName());
    Storage::disk('public')->assertExists($media->path);

    $upload->assertSee('value="'.$path.'"', false);
    Livewire::test(MediaIndex::class)->assertSee('value="'.$path.'"', false);
    Livewire::test(MediaEdit::class, ['media' => $media])->assertSee('value="'.$path.'"', false);

    $markup = $type === 'image'
        ? '<img src="'.$path.'" alt="Training photo">'
        : '<a href="'.$path.'">View document</a>';

    $page = Page::factory()->published()->create([
        'editor_mode' => $mode,
        'content' => $markup,
    ]);

    $this->app['auth']->logout();

    $this->get($path)
        ->assertOk()
        ->assertHeader('Content-Type', $media->mime_type)
        ->assertStreamedContent(Storage::disk('public')->get($media->path));

    $this->get(route('pages.show', $page->slug))
        ->assertOk()
        ->assertSee(($type === 'image' ? 'src' : 'href').'="'.$path.'"', false);
})->with([
    'image in visual editor' => ['image', 'visual'],
    'image in HTML editor' => ['image', 'html'],
    'document in visual editor' => ['document', 'visual'],
    'document in HTML editor' => ['document', 'html'],
]);

it('clears the previous public path when a private file is uploaded', function (): void {
    $upload = Livewire::actingAs($this->admin)
        ->test(MediaUpload::class)
        ->set('file', UploadedFile::fake()->image('public.jpg', 100, 80))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Public image path');

    $path = app(SiteUrlService::class)->portable(app(MediaUrlService::class)->namedOriginal(MediaAsset::query()->sole()));

    $upload->set('file', UploadedFile::fake()->image('private.jpg', 100, 80))
        ->set('visibility', MediaVisibility::Internal->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('This file is private.')
        ->assertDontSee('Public image path')
        ->assertDontSee('value="'.$path.'"', false);
});

it('does not expose private disk files or trashed assets as public paths', function (): void {
    $private = MediaAsset::factory()->create([
        'visibility' => MediaVisibility::Public->value,
        'disk' => 'local',
    ]);
    $trashed = MediaAsset::factory()->create();
    $trashed->delete();

    expect(app(MediaUrlService::class)->original($private))->toBeNull()
        ->and(app(MediaUrlService::class)->original($trashed))->toBeNull();
});

it('provides stable document paths after saving even when table filters hide the upload', function (bool $withSinhala): void {
    $upload = Livewire::actingAs($this->admin)
        ->test(DocumentUploads::class)
        ->set('search', 'unrelated-filter')
        ->set('title', 'Training instructions')
        ->set('englishFile', UploadedFile::fake()->createWithContent('english.pdf', "%PDF-1.4\nEnglish\n%%EOF\n"));

    if ($withSinhala) {
        $upload->set('sinhalaFile', UploadedFile::fake()->createWithContent('sinhala.pdf', "%PDF-1.4\nSinhala\n%%EOF\n"));
    }

    $upload->call('saveAndPublish')->assertHasNoErrors()->assertSee('Copy Path');
    $document = Document::query()->sole();

    $this->app['auth']->logout();

    foreach (['en', 'si'] as $locale) {
        $path = app(SiteUrlService::class)->portable(route('document-files.view', ['locale' => $locale, 'slug' => $document->slug]));
        $upload->assertSee('value="'.$path.'"', false);

        $page = Page::factory()->published()->create([
            'content' => '<a href="'.$path.'">Training instructions</a>',
        ]);
        $this->get(route('pages.show', $page->slug))
            ->assertOk()->assertSee('href="'.$path.'"', false);
        $this->get($path)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    Livewire::actingAs($this->admin)->test(DocumentUploads::class)
        ->assertSee('English document path')
        ->assertSee('Sinhala document path');

    $upload->call('delete', $document->id)->assertHasNoErrors();
    $this->get(route('document-files.view', ['locale' => 'en', 'slug' => $document->slug]))->assertNotFound();
})->with(['shared file' => false, 'separate language files' => true]);
