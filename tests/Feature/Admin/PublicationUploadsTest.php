<?php

use App\Livewire\Admin\Publications\PublicationUploads;
use App\Models\Publication;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PublicationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function publicationTestPdf(string $name = 'journal.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
}

beforeEach(function (): void {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->administrator = User::factory()->create();
    $this->administrator->assignRole('Site Administrator');
    $this->actingAs($this->administrator);
});

it('uploads a publication and cover privately and publishes it from the admin panel', function (): void {
    Livewire::test(PublicationUploads::class)->call('create')->set('title', 'Signals Journal')
        ->set('pdf', publicationTestPdf())->set('cover', UploadedFile::fake()->image('cover.jpg', 600, 800))
        ->call('saveAndPublish')->assertHasNoErrors()->assertSet('formOpen', false);
    $record = Publication::query()->sole();
    expect($record->is_published)->toBeTrue()->and($record->disk)->toBe('local');
    Storage::disk('local')->assertExists([$record->pdf_path, $record->cover_path]);
    $this->assertDatabaseHas('audit_logs', ['event' => 'publications.created', 'subject_id' => $record->id]);
    $this->get(route('admin.publications.index'))->assertOk()->assertSeeText('Signals Journal');
});

it('saves Sinhala publications as separate localized PDF records', function (): void {
    Livewire::test(PublicationUploads::class)->call('create')->set('locale', 'si')->set('title', 'සිංහල සඟරාව')
        ->set('pdf', publicationTestPdf('sinhala-journal.pdf'))
        ->set('cover', UploadedFile::fake()->image('sinhala-cover.jpg', 600, 800))
        ->call('saveAndPublish')->assertHasNoErrors();

    $record = Publication::query()->sole();
    expect($record->locale)->toBe('si')->and($record->title)->toBe('සිංහල සඟරාව');
    $this->get('/publications')->assertOk()->assertDontSeeText('සිංහල සඟරාව');
    $this->get('/si/publications')->assertOk()->assertSeeText('සිංහල සඟරාව');
});

it('keeps issue metadata and filters the administration list by file language', function (): void {
    Livewire::test(PublicationUploads::class)->call('create')->set('title', 'English issue')
        ->set('issueNumber', 'Volume 12 / Issue 2')->set('issueYear', '2026')
        ->set('pdf', publicationTestPdf())->set('cover', UploadedFile::fake()->image('issue.jpg', 600, 800))
        ->call('saveAndPublish')->assertHasNoErrors();
    $record = Publication::query()->where('title', 'English issue')->sole();
    expect($record->issue_number)->toBe('Volume 12 / Issue 2')->and((int) $record->issue_year)->toBe(2026);
    Livewire::test(PublicationUploads::class)->call('create')->set('locale', 'si')->set('title', 'Sinhala issue')
        ->set('pdf', publicationTestPdf('si.pdf'))->set('cover', UploadedFile::fake()->image('si.jpg', 600, 800))
        ->call('save')->assertHasNoErrors();
    Livewire::test(PublicationUploads::class)->set('languageFilter', 'si')->assertSeeText('Sinhala issue')->assertDontSeeText('English issue');
    $this->get('/publications')->assertOk()->assertSeeText('Volume 12 / Issue 2');
});

it('does not save a PDF until its cover upload is complete', function (): void {
    Livewire::test(PublicationUploads::class)->call('create')->set('title', 'Incomplete upload')
        ->set('pdf', publicationTestPdf())->call('save')->assertHasErrors(['cover' => 'required']);
    expect(Publication::query()->count())->toBe(0);
});

it('starts every new publication as a private draft even for an administrator', function (): void {
    Livewire::test(PublicationUploads::class)->call('create')->assertSet('isPublished', false)
        ->set('title', 'Work in progress')->set('pdf', publicationTestPdf())
        ->set('cover', UploadedFile::fake()->image('cover.jpg', 600, 800))
        ->call('save')->assertHasNoErrors();
    $publication = Publication::query()->sole();
    expect($publication->is_published)->toBeFalse();
    $this->get(route('publications.download', ['publication' => $publication->uuid]))->assertNotFound();
});

it('offers a short PDF path only while the publication is publicly available', function (): void {
    $record = app(PublicationService::class)->save($this->administrator, 'Public PDF path', '', true, publicationTestPdf(), UploadedFile::fake()->image('cover.jpg', 600, 800));
    $path = '/publications/'.$record->uuid.'/pdf';
    Livewire::test(PublicationUploads::class)->assertSee('value="'.$path.'"', false);
    $record->update(['is_published' => false]);
    Livewire::test(PublicationUploads::class)->assertDontSee('value="'.$path.'"', false);
    $record->update(['is_published' => true]);
    $record->delete();
    Livewire::test(PublicationUploads::class)->set('statusFilter', 'trashed')->assertDontSee('value="'.$path.'"', false);
});

it('restores trashed publications as drafts without publishing or losing their files', function (): void {
    $record = app(PublicationService::class)->save($this->administrator, 'Recoverable journal', '', true, publicationTestPdf(), UploadedFile::fake()->image('cover.jpg', 600, 800));
    Livewire::test(PublicationUploads::class)->call('delete', $record->id)
        ->set('statusFilter', 'trashed')->assertSeeText('Recoverable journal')->assertSeeText('Restore as draft')
        ->call('restore', $record->id)->assertHasNoErrors();
    expect($record->fresh()->trashed())->toBeFalse()->and($record->fresh()->is_published)->toBeFalse();
    Storage::disk('local')->assertExists([$record->pdf_path, $record->cover_path]);
    $this->get(route('publications.pdf', ['publication' => $record->uuid]))->assertNotFound();
    $this->assertDatabaseHas('audit_logs', ['event' => 'publications.restored', 'subject_id' => $record->id]);
});

it('requires delete permission to restore a publication and publish permission to save and publish', function (): void {
    $record = app(PublicationService::class)->save($this->administrator, 'Private draft', '', false, publicationTestPdf(), UploadedFile::fake()->image('cover.jpg', 600, 800));
    $record->delete();
    $editor = User::factory()->create();
    $editor->givePermissionTo(['documents.view', 'documents.create', 'media.upload']);
    $this->actingAs($editor);
    Livewire::test(PublicationUploads::class)->call('restore', $record->id)->assertForbidden();
    Livewire::test(PublicationUploads::class)->call('saveAndPublish')->assertForbidden();
    expect($record->fresh()->trashed())->toBeTrue();
});

it('shows header validation errors while keeping the header editor open', function (): void {
    Livewire::test(PublicationUploads::class)->call('toggleHeaderSettings')->set('pageTitleEn', '')
        ->call('saveHeaderSettings')->assertHasErrors(['pageTitleEn'])->assertSet('headerSettingsOpen', true)
        ->assertSeeText('required');
});

it('rejects a non PDF file disguised with a PDF extension', function (): void {
    Livewire::test(PublicationUploads::class)->call('create')->set('title', 'Invalid file')
        ->set('pdf', UploadedFile::fake()->createWithContent('fake.pdf', '<html>not a PDF</html>'))
        ->set('cover', UploadedFile::fake()->image('cover.jpg', 600, 800))
        ->call('save')->assertHasErrors(['pdf']);
    expect(Publication::query()->count())->toBe(0);
});

it('lets a content editor save drafts but prevents publishing through a tampered flag', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');
    $this->actingAs($editor);
    Livewire::test(PublicationUploads::class)->call('create')->assertSet('isPublished', false)->set('title', 'Editor draft')
        ->set('pdf', publicationTestPdf())->set('cover', UploadedFile::fake()->image('cover.jpg', 600, 800))
        ->call('save')->assertHasNoErrors();
    expect(Publication::query()->sole()->is_published)->toBeFalse();
    Livewire::test(PublicationUploads::class)->call('create')->set('title', 'Unauthorized publication')->set('isPublished', true)
        ->set('pdf', publicationTestPdf())->set('cover', UploadedFile::fake()->image('cover.jpg', 600, 800))
        ->call('save')->assertForbidden();
    expect(Publication::query()->count())->toBe(1);
});

it('keeps the current PDF for title edits and replaces both PDF and cover together', function (): void {
    $service = app(PublicationService::class);
    $record = $service->save($this->administrator, 'Original title', '', true, publicationTestPdf(), UploadedFile::fake()->image('cover.jpg', 600, 800));
    $oldPdf = $record->pdf_path;
    $oldCover = $record->cover_path;
    Livewire::test(PublicationUploads::class)->call('edit', $record->id)->set('title', 'Updated title')->call('save')->assertHasNoErrors();
    expect($record->fresh()->pdf_path)->toBe($oldPdf);
    Livewire::test(PublicationUploads::class)->call('edit', $record->id)->set('pdf', publicationTestPdf('replacement.pdf'))
        ->set('cover', UploadedFile::fake()->image('replacement.jpg', 500, 700))->call('save')->assertHasNoErrors();
    $record->refresh();
    expect($record->pdf_path)->not->toBe($oldPdf)->and($record->cover_width)->toBe(500);
    Storage::disk('local')->assertMissing([$oldPdf, $oldCover]);
    Storage::disk('local')->assertExists([$record->pdf_path, $record->cover_path]);
});

it('allows replacing a publication cover image without replacing its PDF', function (): void {
    $service = app(PublicationService::class);
    $record = $service->save($this->administrator, 'Signals Journal', '', true, publicationTestPdf(), UploadedFile::fake()->image('cover.jpg', 600, 800));
    $oldPdf = $record->pdf_path;
    $oldCover = $record->cover_path;

    $updated = $service->save(
        actor: $this->administrator,
        title: 'Signals Journal',
        titleSi: '',
        publish: true,
        pdf: null,
        cover: UploadedFile::fake()->image('new-cover.jpg', 500, 700),
        publication: $record,
    );

    expect($updated->pdf_path)->toBe($oldPdf)->and($updated->cover_path)->not->toBe($oldCover)
        ->and($updated->cover_width)->toBe(500);
    Storage::disk('local')->assertExists($oldPdf);
    Storage::disk('local')->assertMissing($oldCover);
});

it('unpublishes and deletes publications without exposing their private files', function (): void {
    $record = app(PublicationService::class)->save($this->administrator, 'Publication', '', true, publicationTestPdf(), UploadedFile::fake()->image('cover.jpg', 600, 800));
    Livewire::test(PublicationUploads::class)->call('unpublish', $record->id);
    $this->get(route('publications.pdf', ['publication' => $record->uuid]))->assertNotFound();
    Livewire::test(PublicationUploads::class)->call('publish', $record->id)->call('delete', $record->id);
    $this->assertSoftDeleted('publications', ['id' => $record->id]);
    $this->get(route('publications.cover', ['publication' => $record->uuid]))->assertNotFound();
});

it('removes newly stored files when saving the database record fails', function (): void {
    Event::listen('eloquent.created: '.Publication::class, function (): void {
        throw new RuntimeException('Simulated database failure');
    });
    try {
        expect(fn () => app(PublicationService::class)->save($this->administrator, 'Rollback', '', true, publicationTestPdf(), UploadedFile::fake()->image('cover.jpg', 600, 800)))
            ->toThrow(RuntimeException::class, 'Simulated database failure');
    } finally {
        Event::forget('eloquent.created: '.Publication::class);
    }
    expect(Publication::query()->count())->toBe(0)->and(Storage::disk('local')->allFiles('publications'))->toBe([]);
});

it('denies publication management to an account without document permissions', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');
    $this->actingAs($user)->get(route('admin.publications.index'))->assertForbidden();
});

it('enforces a 50 MB limit on publication PDFs', function (): void {
    $oversizedPdf = publicationTestPdf('huge.pdf')->size(51201);

    Livewire::test(PublicationUploads::class)->call('create')->set('title', 'Oversized PDF')
        ->set('pdf', $oversizedPdf)
        ->set('cover', UploadedFile::fake()->image('cover.jpg', 600, 800))
        ->call('save')->assertHasErrors(['pdf']);

    $validPdf = publicationTestPdf('valid-limit.pdf')->size(51200);

    Livewire::test(PublicationUploads::class)->call('create')->set('title', 'Valid 50MB PDF')
        ->set('pdf', $validPdf)
        ->set('cover', UploadedFile::fake()->image('cover.jpg', 600, 800))
        ->call('save')->assertHasNoErrors();
});

it('allows authorized administrators to customize publication page header title and description', function (): void {
    Livewire::test(PublicationUploads::class)
        ->assertSet('headerSettingsOpen', false)
        ->call('toggleHeaderSettings')
        ->assertSet('headerSettingsOpen', true)
        ->set('pageTitleEn', 'The Corps Journal')
        ->set('pageTitleSi', 'බලකා සඟරාව')
        ->set('pageSubtitleEn', 'Custom subtitle description')
        ->call('saveHeaderSettings')
        ->assertHasNoErrors()
        ->assertSet('headerSettingsOpen', false);

    $settings = SiteSetting::current();
    expect($settings->publications_title_en)->toBe('The Corps Journal')
        ->and($settings->publications_title_si)->toBe('බලකා සඟරාව')
        ->and($settings->publications_subtitle_en)->toBe('Custom subtitle description');

    $this->get('/publications')->assertOk()->assertSeeText('The Corps Journal')
        ->assertSeeText('Custom subtitle description');
    $this->get('/si/publications')->assertOk()->assertSeeText('බලකා සඟරාව');
});
