<?php

use App\Enums\PageStatus;
use App\Livewire\Admin\Pages\PageEdit;
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

test('content editor can access draft page edit form', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    $page = Page::factory()->create([
        'status' => PageStatus::Draft->value,
    ]);

    $this->actingAs($editor)
        ->get(route('admin.pages.edit', $page))
        ->assertOk()
        ->assertSee('Edit Page')
        ->assertSee($page->title);
});

test('user without update permission cannot edit a page', function (): void {
    $auditor = User::factory()->create();
    $auditor->assignRole('Auditor');

    $page = Page::factory()->create();

    $this->actingAs($auditor)
        ->get(route('admin.pages.edit', $page))
        ->assertForbidden();
});

test('content editor can update a draft page', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    $page = Page::factory()->create([
        'title' => 'Old Page Title',
        'slug' => 'old-page-title',
        'status' => PageStatus::Draft->value,
    ]);

    Livewire::actingAs($editor)
        ->test(PageEdit::class, [
            'page' => $page,
        ])
        ->set('title', 'Updated Page Title')
        ->set('slug', 'updated-page-title')
        ->set('excerpt', 'Updated page description.')
        ->set('content', 'Updated page content.')
        ->call('save')
        ->assertHasNoErrors();

    $page->refresh();

    expect($page->title)
        ->toBe('Updated Page Title')
        ->and($page->slug)
        ->toBe('updated-page-title')
        ->and($page->excerpt)
        ->toBe('Updated page description.')
        ->and($page->content)
        ->toBe('Updated page content.')
        ->and($page->updated_by)
        ->toBe($editor->id);
});

test('duplicate slug cannot be assigned during page editing', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    Page::factory()->create([
        'slug' => 'existing-page',
    ]);

    $page = Page::factory()->create([
        'slug' => 'editable-page',
        'status' => PageStatus::Draft->value,
    ]);

    Livewire::actingAs($editor)
        ->test(PageEdit::class, [
            'page' => $page,
        ])
        ->set('slug', 'existing-page')
        ->call('save')
        ->assertHasErrors([
            'slug' => 'unique',
        ]);
});

test('an existing draft can reuse a slug from trash', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    $deletedPage = Page::factory()->create(['slug' => 'slsc-flag']);
    $deletedPage->delete();

    $page = Page::factory()->create([
        'slug' => 'slsc-flag-5',
        'status' => PageStatus::Draft->value,
    ]);

    Livewire::actingAs($editor)
        ->test(PageEdit::class, ['page' => $page])
        ->set('slug', 'slsc-flag')
        ->call('save')
        ->assertHasNoErrors();

    expect($page->fresh()->slug)->toBe('slsc-flag')
        ->and($deletedPage->fresh()->trashed())->toBeTrue()
        ->and($deletedPage->fresh()->slug)->toBe('slsc-flag');
});

test('updating a page creates a safe change audit record', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    $oldContent = 'Old internal page body.';
    $newContent = 'New internal page body.';

    $page = Page::factory()->create([
        'title' => 'Original Title',
        'slug' => 'original-title',
        'content' => $oldContent,
        'status' => PageStatus::Draft->value,
    ]);

    Livewire::actingAs($editor)
        ->test(PageEdit::class, [
            'page' => $page,
        ])
        ->set('title', 'Changed Title')
        ->set('content', $newContent)
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::query()
        ->where('event', 'pages.updated')
        ->latest('id')
        ->firstOrFail();

    expect($log->actor_id)
        ->toBe($editor->id)
        ->and($log->subject_id)
        ->toBe($page->id)
        ->and($log->old_values)
        ->toMatchArray([
            'title' => 'Original Title',
            'content_length' => mb_strlen($oldContent),
        ])
        ->and($log->new_values)
        ->toMatchArray([
            'title' => 'Changed Title',
            'content_length' => mb_strlen($newContent),
            'content_changed' => true,
        ]);

    $encodedValues = json_encode([
        $log->old_values,
        $log->new_values,
    ]);

    expect($encodedValues)
        ->not->toContain($oldContent)
        ->not->toContain($newContent);
});

test('published pages cannot be directly edited', function (): void {
    $editor = User::factory()->create();
    $editor->assignRole('Content Editor');

    $page = Page::factory()
        ->published()
        ->create();

    $this->actingAs($editor)
        ->get(route('admin.pages.edit', $page))
        ->assertStatus(409);
});
