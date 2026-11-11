<?php

use App\Enums\PageEditorMode;
use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use Spatie\Permission\Models\Permission;

it('renders administrator preview with modern SLSC theme and preview banner', function (): void {
    Permission::firstOrCreate(['name' => 'admin.access', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'pages.view', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'pages.view']);

    $page = Page::factory()->create([
        'title' => 'Signal Corps Flag Page',
        'slug' => 'signal-corps-flag-preview-test',
        'excerpt' => 'History and symbolism of the Signal Corps flag.',
        'content' => '<p><img src="/media-files/slsc.jpg" alt="flag" style="margin: 1.5rem auto; display: block;" />Detailed explanation of colors.</p>',
        'editor_mode' => PageEditorMode::Visual->value,
        'status' => PageStatus::Published->value,
        'show_title' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.pages.preview', $page));

    $response->assertOk()
        ->assertSee('admin-preview-bar', false)
        ->assertSee('Administrator Preview Mode', false)
        ->assertSee('cms-page-head', false)
        ->assertSee('page-article-card', false)
        ->assertSee('awcms-content', false)
        ->assertSee('Signal Corps Flag Page', false)
        ->assertSee('History and symbolism of the Signal Corps flag.', false)
        ->assertSee('Detailed explanation of colors.', false);
});
