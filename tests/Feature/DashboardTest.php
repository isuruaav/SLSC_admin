<?php

use App\Models\News;
use App\Models\Page;
use App\Models\Publication;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected to the login page', function (): void {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authorized users are redirected to the admin dashboard', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole('Super Administrator');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Continue Editing');
});

test('dashboard offers drafts, publication counts and an upload shortcut', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Site Administrator');
    Page::factory()->create(['title' => 'Unfinished history']);
    News::factory()->create(['title' => 'Unfinished news', 'status' => 'draft']);
    $publication = Publication::query()->create(['title' => 'Unfinished journal', 'disk' => 'local', 'pdf_path' => 'publications/draft.pdf', 'cover_path' => 'publications/draft.jpg', 'original_name' => 'draft.pdf', 'size_bytes' => 10, 'cover_width' => 600, 'cover_height' => 800, 'is_published' => false]);
    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()
        ->assertSeeText('Unfinished history')->assertSeeText('Unfinished news')->assertSeeText('Unfinished journal')
        ->assertSeeText('Upload Publication')->assertViewHas('publicationCount', 1);
    $this->get(route('admin.publications.index', ['edit' => $publication->id]))->assertOk()->assertSeeText('Edit publication');
});

test('dashboard draft shortcuts respect content permissions', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'dashboard.view']);
    Page::factory()->create(['title' => 'Restricted draft']);
    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()->assertDontSeeText('Restricted draft')->assertDontSeeText('Upload Publication');
});
