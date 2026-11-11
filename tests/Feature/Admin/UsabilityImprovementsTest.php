<?php

use App\Actions\Fortify\ResetUserPassword;
use App\Enums\DocumentStatus;
use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Enums\ThemeLayoutLocale;
use App\Enums\ThemeLayoutRegion;
use App\Livewire\Admin\AuditLogs\AuditLogIndex;
use App\Livewire\Admin\ContactMessages\ContactMessageIndex;
use App\Livewire\Admin\Courses\CourseIndex;
use App\Livewire\Admin\Documents\DocumentUploads;
use App\Livewire\Admin\Galleries\GalleryEdit;
use App\Livewire\Admin\Media\MediaUpload;
use App\Livewire\Admin\Settings\SystemTools;
use App\Livewire\Admin\ThemeLayouts\ThemeLayoutIndex;
use App\Livewire\Admin\Users\UserCreate;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\Document;
use App\Models\FirstAppointmentHolder;
use App\Models\Gallery;
use App\Models\MediaAsset;
use App\Models\Organization;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\SpecialProgram;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ContentLinkInspector;
use App\Services\GalleryService;
use App\Services\MediaUploadService;
use App\Services\OrganizationImportService;
use App\Services\OrganizationRevisionService;
use App\Services\RedirectService;
use App\Services\SiteBackupService;
use App\Services\ThemeLayoutService;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Site Administrator');
    $this->actingAs($this->admin);
    config()->set('awcms.active_theme', 'slsc');
});

it('stores document drafts privately and restores deleted documents without republishing', function (): void {
    $pdf = UploadedFile::fake()->createWithContent('draft.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    $component = Livewire::test(DocumentUploads::class)->set('title', 'Private working copy')->set('englishFile', $pdf)->call('save')->assertHasNoErrors();
    $document = Document::query()->sole();
    $media = $document->versions()->with('media')->firstOrFail()->media;
    expect($document->status)->toBe(DocumentStatus::Draft)->and($media->disk)->toBe(config('media.disks.private', 'local'));
    Storage::disk($media->disk)->assertExists($media->path);
    $this->get(route('documents.download', $document->slug))->assertNotFound();
    $component->call('publish', $document->id)->assertHasNoErrors();
    $this->get(route('documents.download', $document->slug))->assertOk();
    $component->call('unpublish', $document->id)->call('delete', $document->id)->set('statusFilter', 'trashed')->assertSeeText('Private working copy')->call('restore', $document->id);
    expect($document->fresh()->trashed())->toBeFalse()->and($document->fresh()->status)->toBe(DocumentStatus::Draft);
});

it('lets training staff manage bilingual courses without site settings access', function (): void {
    $trainer = User::factory()->create();
    $trainer->assignRole('Training Manager');
    $this->actingAs($trainer)->get(route('admin.courses.index'))->assertOk();
    $this->get(route('admin.site-settings.index'))->assertForbidden();
    Livewire::test(CourseIndex::class)->call('create')->set('name', 'Radio Course')->set('nameSi', 'ගුවන්විදුලි පාඨමාලාව')
        ->set('qualifications', ['Basic training'])->set('qualificationsSi', ['මූලික පුහුණුව'])->call('save')->assertHasNoErrors();
    expect(Course::query()->where('name', 'Radio Course')->sole()->qualifications()->sole()->qualification_si)->toBe('මූලික පුහුණුව');
    $this->get('/si/courses?q=Radio')->assertOk()->assertSeeText('මූලික පුහුණුව');
});

it('restores organization translations and structured blocks from a recovery snapshot', function (): void {
    $organization = Organization::query()->create(['slug' => 'snapshot-unit', 'short_name' => 'Original', 'status' => 'draft']);
    $organization->translations()->create(['locale' => 'en', 'title' => 'Original Unit']);
    $organization->contentBlocks()->create(['locale' => 'en', 'block_type' => 'table', 'sort_order' => 1, 'structured_data' => ['headers' => ['Year'], 'rows' => [['2020']]]]);
    $service = app(OrganizationRevisionService::class);
    $snapshot = $service->capture($organization, $this->admin->id, 'Before overwrite');
    $organization->update(['short_name' => 'Changed']);
    $organization->translations()->delete();
    $organization->contentBlocks()->delete();
    $restored = $service->restore($snapshot->id, $this->admin);
    expect($restored->short_name)->toBe('Original')->and($restored->titleForLocale('en'))->toBe('Original Unit')
        ->and($restored->contentBlocks()->sole()->structured_data['rows'])->toBe([['2020']])->and($restored->status->value)->toBe('draft');
});

it('assigns enquiries and preserves notes when reopening a resolved message', function (): void {
    $message = ContactMessage::query()->create(['name' => 'Visitor', 'email' => 'visitor@example.test', 'subject' => 'Course question', 'message' => 'Please help.', 'status' => 'new']);
    Livewire::test(ContactMessageIndex::class)->call('openMessage', $message->id)->set('assignedTo', (string) $this->admin->id)->set('notes', 'Awaiting details.')
        ->call('saveMessageDetails')->assertHasNoErrors()->call('resolve', $message->id)->call('reopen', $message->id);
    expect($message->fresh()->internal_notes)->toBe('Awaiting details.')->and($message->fresh()->assigned_to)->toBe($this->admin->id)
        ->and($message->fresh()->status)->toBe('read')->and($message->fresh()->resolved_at)->toBeNull();
});

it('does not assign enquiries to a user without contact management permission', function (): void {
    $message = ContactMessage::query()->create(['name' => 'Visitor', 'email' => 'visitor@example.test', 'subject' => 'Help', 'message' => 'Question', 'status' => 'new']);
    $user = User::factory()->create();
    Livewire::test(ContactMessageIndex::class)->call('openMessage', $message->id)->set('assignedTo', (string) $user->id)->call('saveMessageDetails')->assertHasErrors(['assignedTo']);
});

it('restores header revisions as drafts while preserving the live layout', function (): void {
    $service = app(ThemeLayoutService::class);
    $layout = $service->saveDraft('slsc', ThemeLayoutRegion::Header, ThemeLayoutLocale::English, $this->admin, '<nav><a href="/contact">Original</a></nav>', '');
    $revision = $layout->revisions()->firstOrFail();
    $service->saveDraft('slsc', ThemeLayoutRegion::Header, ThemeLayoutLocale::English, $this->admin, '<nav><a href="/news">Updated</a></nav>', '');
    $service->publish($layout->fresh(), $this->admin);
    Livewire::test(ThemeLayoutIndex::class)->call('restoreRevision', $revision->id)->assertHasNoErrors();
    expect($layout->fresh()->draft_html)->toContain('Original')->and($layout->fresh()->published_html)->toContain('Updated');
});

it('detects unpublished internal targets and prevents multi-hop redirect cycles', function (): void {
    $page = Page::factory()->create(['slug' => 'unpublished-target']);
    expect(app(ContentLinkInspector::class)->inspect('/pages/'.$page->slug)['status'])->toBe('broken');
    expect(app(ContentLinkInspector::class)->inspect('https://example.com/path')['status'])->toBe('external');
    $service = app(RedirectService::class);
    $first = $service->save(null, $this->admin, '/cycle-a', '/contact', 301, true);
    $service->save(null, $this->admin, '/cycle-b', '/cycle-a', 301, true);
    expect(fn () => $service->save($first, $this->admin, '/cycle-a', '/cycle-b', 301, true))->toThrow(ValidationException::class);
    expect($first->fresh()->destination_url)->toBe('/contact');
});

it('reuses duplicate files in a batch without uploading extra copies', function (): void {
    $image = UploadedFile::fake()->image('first.jpg', 120, 120);
    $copy = UploadedFile::fake()->createWithContent('same.jpg', file_get_contents($image->getPathname()));
    $component = Livewire::test(MediaUpload::class)->set('batchFiles', [$image, $copy])->call('uploadBatch')->assertHasNoErrors();
    expect(MediaAsset::query()->count())->toBe(1);
    $results = $component->get('batchResults');
    expect($results[0]['status'])->toBe('uploaded')->and($results[1]['status'])->toBe('reused');
});

it('creates an invitation without sending mail and completes first-time account verification', function (): void {
    Notification::fake();
    $component = Livewire::test(UserCreate::class)->set('inviteByLink', true)->set('name', 'Invited Editor')->set('email', 'invited@example.test')->set('role', 'Content Editor')->call('save')->assertHasNoErrors();
    $url = $component->get('invitationUrl');
    expect($url)->toContain('/reset-password/');
    $user = User::query()->where('email', 'invited@example.test')->firstOrFail();
    expect($user->email_verified_at)->toBeNull()->and($user->invited_at)->not->toBeNull();
    $token = basename(parse_url($url, PHP_URL_PATH));
    $credentials = ['email' => $user->email, 'token' => $token, 'password' => 'SafeTestPass#123', 'password_confirmation' => 'SafeTestPass#123'];
    $reset = fn (User $account, string $password) => app(ResetUserPassword::class)->reset($account, ['password' => $password, 'password_confirmation' => $password]);
    expect(Password::reset($credentials, $reset))->toBe(Password::PASSWORD_RESET);
    expect($user->fresh()->email_verified_at)->not->toBeNull()->and($user->fresh()->invitation_accepted_at)->not->toBeNull();
    expect(Password::reset($credentials, $reset))->toBe(Password::INVALID_TOKEN);
    Notification::assertNothingSent();
});

it('hides scheduled programs outside their display period', function (): void {
    SpecialProgram::query()->create(['title_en' => 'Future program', 'url' => '/contact', 'category' => 'heritage', 'is_active' => true, 'starts_at' => now()->addDay()]);
    SpecialProgram::query()->create(['title_en' => 'Expired program', 'url' => '/contact', 'category' => 'heritage', 'is_active' => true, 'ends_at' => now()->subDay()]);
    SpecialProgram::query()->create(['title_en' => 'Current program', 'url' => '/contact', 'category' => 'heritage', 'is_active' => true]);
    expect(SpecialProgram::query()->active()->whereIn('title_en', ['Future program', 'Expired program', 'Current program'])->pluck('title_en')->all())->toBe(['Current program']);
});

it('validates backup schedules and restricts backup tools to settings managers', function (): void {
    Livewire::test(SystemTools::class)->set('frequency', 'weekly')->set('time', '03:30')->set('timezone', 'Asia/Colombo')->set('retention', 8)->call('saveSchedule')->assertHasNoErrors();
    $this->assertDatabaseHas('site_settings', ['backup_frequency' => 'weekly', 'backup_time' => '03:30', 'backup_retention' => 8]);
    Livewire::test(SystemTools::class)->set('timezone', 'Not/AZone')->call('saveSchedule')->assertHasErrors(['timezone']);
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');
    $this->actingAs($editor)->get(route('admin.system-tools'))->assertForbidden();
});

it('keeps successful gallery files and unsaved captions when another file fails', function (): void {
    $gallery = Gallery::factory()->create();
    $component = Livewire::test(GalleryEdit::class, ['gallery' => $gallery])
        ->set('uploads', [UploadedFile::fake()->image('valid.jpg'), UploadedFile::fake()->create('invalid.txt', 1, 'text/plain')])
        ->set('title', 'Unsaved gallery title')->call('uploadImages')->assertHasNoErrors();
    expect($gallery->images()->count())->toBe(1);
    $image = $gallery->images()->sole();
    expect($component->get('imageUploadResults')[0]['success'])->toBeTrue()
        ->and($component->get('imageUploadResults')[1]['success'])->toBeFalse()
        ->and($component->get('uploads'))->toHaveCount(1)
        ->and($component->get('title'))->toBe('Unsaved gallery title');
    $component->set('imageCaptions.'.$image->id, 'Still editing')->call('retryImageUpload', 1);
    expect($component->get('imageCaptions')[$image->id])->toBe('Still editing')
        ->and($gallery->images()->count())->toBe(1);
});

it('saves all gallery captions atomically and rejects another gallery image', function (): void {
    $gallery = Gallery::factory()->create();
    $other = Gallery::factory()->create();
    $upload = app(MediaUploadService::class);
    $service = app(GalleryService::class);
    $firstMedia = $upload->upload(UploadedFile::fake()->image('first.jpg'), MediaType::Image, MediaVisibility::Public, $this->admin);
    $secondMedia = $upload->upload(UploadedFile::fake()->image('second.jpg'), MediaType::Image, MediaVisibility::Public, $this->admin);
    $first = $service->addImage($gallery, $firstMedia, $this->admin, 'Original caption');
    $foreign = $service->addImage($other, $secondMedia, $this->admin);
    $component = Livewire::test(GalleryEdit::class, ['gallery' => $gallery]);
    expect(fn () => $component->set('imageCaptions.'.$first->id, 'Changed caption')->set('imageCaptions.'.$foreign->id, 'Foreign edit')->call('saveAllImageDetails'))->toThrow(ModelNotFoundException::class);
    expect($first->fresh()->caption)->toBe('Original caption')->and($foreign->fresh()->caption)->toBeNull();
    Livewire::test(GalleryEdit::class, ['gallery' => $gallery])->set('imageCaptions.'.$first->id, 'Saved caption')->call('saveAllImageDetails')->assertHasNoErrors();
    expect($first->fresh()->caption)->toBe('Saved caption');
    Livewire::test(GalleryEdit::class, ['gallery' => $gallery])->call('reorderImages', [$foreign->id])->assertHasErrors();
});

it('exports only filtered audit rows and protects spreadsheet formulas', function (): void {
    $logger = app(AuditLogger::class);
    $logger->log(event: 'ux.csv', description: '  =SUM(1,2)', actor: $this->admin);
    $logger->log(event: 'ux.other', description: 'Excluded row', actor: $this->admin);
    $component = Livewire::test(AuditLogIndex::class)->set('event', 'ux.csv');
    $response = $component->instance()->exportCsv();
    ob_start();
    ($response->getCallback())();
    $csv = ob_get_clean();
    expect($csv)->toContain("'=SUM(1,2)")->not->toContain('Excluded row');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('filters historical appointments by complete year ranges in timeline order', function (): void {
    $earlier = FirstAppointmentHolder::factory()->create(['name_en' => 'Earlier officer', 'period_en' => '1998 - 2003', 'sort_order' => 9]);
    $later = FirstAppointmentHolder::factory()->create(['name_en' => 'Later officer', 'start_date' => '2002-01-01', 'end_date' => '2005-12-31', 'sort_order' => 1]);
    FirstAppointmentHolder::factory()->create(['name_en' => 'Outside selected year', 'period_en' => '2010 - 2015']);
    $this->get(route('first-appointments.index', ['year' => 2002, 'view' => 'timeline']))->assertOk()
        ->assertSeeInOrder([$earlier->name_en, $later->name_en])->assertDontSeeText('Outside selected year');
});

it('runs a scheduled backup once at the selected local time', function (): void {
    Cache::flush();
    SiteSetting::current()->forceFill(['backup_frequency' => 'weekly', 'backup_time' => '03:30', 'backup_timezone' => 'Asia/Colombo', 'backup_retention' => 5])->save();
    Artisan::shouldReceive('call')->once()->with('site:backup', ['--keep' => 5])->andReturn(0);
    $this->travelTo(CarbonImmutable::parse('2026-11-09 03:29:00', 'Asia/Colombo'));
    $service = app(SiteBackupService::class);
    $service->runScheduled();
    $this->travel(1)->minutes();
    $service->runScheduled();
    $service->runScheduled();
    expect(Cache::get('site-backup-status')['success'])->toBeTrue();
});

it('publishes the latest gallery captions with the title', function (): void {
    $gallery = Gallery::factory()->create();
    $component = Livewire::test(GalleryEdit::class, ['gallery' => $gallery])
        ->set('uploads', [UploadedFile::fake()->image('gallery.jpg')])->call('uploadImages')->assertHasNoErrors();
    $image = $gallery->images()->sole();
    $component->set('imageCaptions.'.$image->id, 'Caption entered before publishing')
        ->set('title', 'Ready gallery')->call('publish')->assertHasNoErrors();
    expect($gallery->fresh()->status->value)->toBe('published')->and($gallery->fresh()->title)->toBe('Ready gallery')
        ->and($image->fresh()->caption)->toBe('Caption entered before publishing');
});

it('finds public organizations and galleries while excluding drafts from discovery', function (): void {
    $public = Organization::factory()->create(['status' => 'published', 'published_at' => now()]);
    $public->translations()->create(['locale' => 'en', 'title' => 'Discovery public organization']);
    $draft = Organization::factory()->create();
    $draft->translations()->create(['locale' => 'en', 'title' => 'Discovery draft organization']);
    $gallery = Gallery::factory()->published()->create(['title' => 'Discovery public gallery']);
    Gallery::factory()->create(['title' => 'Discovery draft gallery']);
    $this->get(route('search', ['q' => 'Discovery']))->assertOk()->assertSeeText('Discovery public organization')->assertSeeText($gallery->title)
        ->assertDontSeeText('Discovery draft organization')->assertDontSeeText('Discovery draft gallery');
    $sitemap = $this->get('/sitemap.xml')->assertOk();
    $sitemap->assertSee(route('organization.show', $public->slug), false)->assertDontSee(route('organization.show', $draft->slug), false);
});

it('keeps published organizations safe from import overwrites', function (): void {
    $service = app(OrganizationImportService::class);
    $result = $service->importUnit('ocso', false, $this->admin->id);
    $organization = $result['organization'];
    $organization->update(['status' => 'published', 'published_at' => now()]);
    $result = $service->importUnit('ocso', true, $this->admin->id);
    expect($result['success'])->toBeFalse()->and($organization->fresh()->status->value)->toBe('published');
});
