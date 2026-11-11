<?php

use App\Enums\PageStatus;
use App\Livewire\Admin\Pages\PageCreate;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(
        RolesAndPermissionsSeeder::class,
    );
});

test('content editor can access page creation form', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    $this->actingAs($editor)
        ->get(route('admin.pages.create'))
        ->assertOk()
        ->assertSee('Create Page');
});

test('user without page creation permission is forbidden', function (): void {
    $auditor = User::factory()->create();
    $auditor->assignRole('Auditor');

    $this->actingAs($auditor)
        ->get(route('admin.pages.create'))
        ->assertForbidden();
});

test('content editor can create a draft page', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    Livewire::actingAs($editor)
        ->test(PageCreate::class)
        ->set('title', 'About the Regiment')
        ->set('excerpt', 'A short history of the regiment.')
        ->set('content', 'Regimental history content.')
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::query()
        ->where('title', 'About the Regiment')
        ->firstOrFail();

    expect($page->slug)
        ->toBe('about-the-regiment')
        ->and($page->status)
        ->toBe(PageStatus::Draft)
        ->and($page->created_by)
        ->toBe($editor->id)
        ->and($page->updated_by)
        ->toBe($editor->id);
});

test('duplicate page slugs receive a numeric suffix', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    Page::factory()->create([
        'title' => 'About Us',
        'slug' => 'about-us',
    ]);

    Livewire::actingAs($editor)
        ->test(PageCreate::class)
        ->set('title', 'About Us')
        ->set('slug', 'about-us')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pages', [
        'title' => 'About Us',
        'slug' => 'about-us-2',
        'status' => PageStatus::Draft->value,
    ]);
});

test('new pages can reuse a slug from trash without removing deleted pages', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    $deletedPage = Page::factory()->create([
        'slug' => 'slsc-flag',
    ]);
    $deletedPage->delete();

    for ($attempt = 0; $attempt < 2; $attempt++) {
        Livewire::actingAs($editor)
            ->test(PageCreate::class)
            ->set('title', 'SLSC Flag')
            ->call('save')
            ->assertHasNoErrors();

        $replacement = Page::query()->where('title', 'SLSC Flag')->firstOrFail();

        expect($replacement->slug)->toBe('slsc-flag');

        $replacement->delete();
    }

    expect(Page::onlyTrashed()->where('slug', 'slsc-flag')->count())->toBe(3)
        ->and($deletedPage->fresh()->trashed())->toBeTrue();
});

test('creating a page creates a safe audit record', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    $content = 'Sensitive internal draft body for testing.';

    Livewire::actingAs($editor)
        ->test(PageCreate::class)
        ->set('title', 'Training Information')
        ->set('content', $content)
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::query()
        ->where('title', 'Training Information')
        ->firstOrFail();

    $log = AuditLog::query()
        ->where('event', 'pages.created')
        ->latest('id')
        ->firstOrFail();

    expect($log->actor_id)
        ->toBe($editor->id)
        ->and($log->subject_id)
        ->toBe($page->id)
        ->and($log->new_values)
        ->toMatchArray([
            'title' => 'Training Information',
            'slug' => 'training-information',
            'status' => 'draft',
            'content_present' => true,
        ]);

    $encodedAuditValues = json_encode(
        $log->new_values,
    );

    expect($encodedAuditValues)
        ->not->toContain($content);
});

test('page title is required', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    Livewire::actingAs($editor)
        ->test(PageCreate::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors([
            'title' => 'required',
        ]);

    expect(Page::query()->count())
        ->toBe(0);
});
