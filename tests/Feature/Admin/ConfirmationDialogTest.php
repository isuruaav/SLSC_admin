<?php

use App\Models\News;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

it('renders one accessible Yes No confirmation dialog in the admin layout', function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::findOrCreate('admin.access', 'web');
    Permission::findOrCreate('dashboard.view', 'web');
    $administrator = User::factory()->create(['email_verified_at' => now()]);
    $administrator->givePermissionTo(['admin.access', 'dashboard.view']);

    $response = $this->actingAs($administrator)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('aria-labelledby="admin-confirmation-title"', false)
        ->assertSee('aria-describedby="admin-confirmation-message"', false)
        ->assertSee('No, cancel')
        ->assertSee('Yes, continue')
        ->assertSee('data-confirm-no autofocus', false);

    expect(substr_count($response->getContent(), 'id="admin-confirmation-dialog"'))->toBe(1);
});

it('does not add admin confirmation UI to the public website', function (): void {
    $this->get(route('home'))->assertOk()->assertDontSee('id="admin-confirmation-dialog"', false);
});

it('keeps draft saves immediate and confirms publishing news', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole('Site Administrator');
    $news = News::factory()->create();
    $page = Page::factory()->create();

    $this->actingAs($administrator)->get(route('admin.news.create'))->assertOk()
        ->assertDontSee('wire:confirm="Save this news article as a draft?"', false)
        ->assertSee('wire:confirm="Save and publish this news article now?"', false);
    $this->get(route('admin.news.edit', $news))->assertOk()
        ->assertDontSee('wire:confirm="Save changes to this news draft?"', false);
    $this->get(route('admin.pages.create'))->assertOk()
        ->assertDontSee('wire:confirm="Create this page as a draft?"', false);
    $this->get(route('admin.pages.edit', $page))->assertOk()
        ->assertDontSee('wire:confirm="Save changes to this page?"', false);
});

it('prevents repeat clicks on both mobile and desktop news action buttons while loading', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $administrator = User::factory()->create();
    $administrator->assignRole('Site Administrator');
    News::factory()->create();
    News::factory()->published()->create();

    $response = $this->actingAs($administrator)->get(route('admin.news.index'))->assertOk();
    $document = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    try {
        $document->loadHTML($response->getContent());
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
    $buttons = (new DOMXPath($document))->query('//*[@*[name()="wire:click"]]');
    $checked = 0;

    foreach ($buttons as $button) {
        $action = $button->getAttribute('wire:click');
        if (! preg_match('/^(publishArticle|unpublishArticle|deleteArticle)\(/', $action)) {
            continue;
        }
        expect($button->getAttribute('wire:confirm'))->not->toBe('')
            ->and($button->getAttribute('wire:loading.attr'))->toBe('disabled')
            ->and($button->getAttribute('wire:target'))->toBe($action);
        $checked++;
    }

    expect($checked)->toBe(6);
});
