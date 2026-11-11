<?php

use App\Enums\DocumentStatus;
use App\Livewire\Admin\Documents\DocumentUploads;
use App\Models\Document;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Site Administrator');
    $this->actingAs($this->administrator);
});

it('keeps the upload modal closed until a document action opens it', function (): void {
    Livewire::test(DocumentUploads::class)->assertSet('showEditor', false)
        ->call('create')->assertSet('showEditor', true)->assertSet('editingId', null)
        ->assertSee('data-modal="document-upload-editor"', false)
        ->assertSee('aria-labelledby', false);
});

it('keeps validation errors inside the open modal and closes after saving a draft', function (): void {
    $component = Livewire::test(DocumentUploads::class)->call('create')
        ->set('title', 'Training draft')->call('save')->assertHasErrors('englishFile')
        ->assertSet('showEditor', true)->assertSet('title', 'Training draft')
        ->assertSeeText('Upload at least one document file.');
    $component->set('englishFile', UploadedFile::fake()->createWithContent('training.pdf', "%PDF-1.4\n%%EOF"))
        ->call('save')->assertHasNoErrors()->assertSet('showEditor', false)
        ->assertSet('title', '')->assertSet('englishFile', null);
    expect(Document::query()->sole()->status)->toBe(DocumentStatus::Draft);
});

it('clears cancelled input and selected files before reopening', function (): void {
    $component = Livewire::test(DocumentUploads::class)->call('create')
        ->set('title', 'Cancelled document')->set('englishFile', UploadedFile::fake()->createWithContent('cancelled.pdf', "%PDF-1.4\n%%EOF"));
    $version = $component->get('formVersion');
    $component->call('cancel')->assertSet('showEditor', false)
        ->call('create')->assertSet('showEditor', true)->assertSet('title', '')->assertSet('englishFile', null);
    expect($component->get('formVersion'))->toBeGreaterThan($version);
    expect(Document::query()->count())->toBe(0);
});

it('opens the existing document in the same modal and resets it for a new upload', function (): void {
    $document = Document::factory()->create(['title' => 'Existing instructions']);
    Livewire::test(DocumentUploads::class)->call('edit', $document->id)
        ->assertSet('showEditor', true)->assertSet('editingId', $document->id)
        ->assertSet('title', 'Existing instructions')->assertSeeText('Edit Document')
        ->call('cancel')->call('create')->assertSet('editingId', null)->assertSet('title', '');
});

it('closes the modal after an explicit save and publish', function (): void {
    Livewire::test(DocumentUploads::class)->call('create')->set('title', 'Published instructions')
        ->set('englishFile', UploadedFile::fake()->createWithContent('instructions.pdf', "%PDF-1.4\n%%EOF"))
        ->call('saveAndPublish')->assertHasNoErrors()->assertSet('showEditor', false)->assertSeeText('Document saved and published successfully.');
    expect(Document::query()->sole()->status)->toBe(DocumentStatus::Published);
});

it('opens the modal on direct create and edit routes', function (): void {
    $this->get(route('admin.documents.create'))->assertOk()->assertSee('&quot;showEditor&quot;:true', false);
    $document = Document::factory()->create(['title' => 'Direct edit document']);
    $this->get(route('admin.documents.edit', $document))->assertOk()->assertSee('&quot;showEditor&quot;:true', false)->assertSee('Direct edit document');
});

it('requires creation permission to open a new document modal', function (): void {
    $editor = User::factory()->create();
    $editor->givePermissionTo(['documents.view', 'documents.update']);
    Livewire::actingAs($editor)->test(DocumentUploads::class)->call('create')->assertForbidden();
});
