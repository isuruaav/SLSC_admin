<?php

use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Enums\MediaVisibility;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

it('forbids unauthenticated users from quick media upload', function (): void {
    $this->postJson(route('admin.media.quick-upload'))
        ->assertUnauthorized();
});

it('allows authorized admin user to quick upload an image', function (): void {
    Storage::fake('public');

    Permission::firstOrCreate(['name' => 'admin.access', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'media.upload', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'media.upload']);

    $file = UploadedFile::fake()->image('test-diagram.png', 400, 300);

    $response = $this->actingAs($user)
        ->postJson(route('admin.media.quick-upload'), [
            'file' => $file,
            'title' => 'Test Diagram',
            'alt_text' => 'Custom Alt Description',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'name' => 'test-diagram.png',
            'alt' => 'Custom Alt Description',
        ]);

    $data = $response->json();
    expect($data['url'])->not->toBeEmpty();
    expect($data['path'])->toContain('/media-files/');

    $this->assertDatabaseHas('media_assets', [
        'title' => 'Test Diagram',
        'type' => MediaType::Image->value,
        'visibility' => MediaVisibility::Public->value,
    ]);
});

it('allows authorized user to retrieve recent images with search', function (): void {
    Permission::firstOrCreate(['name' => 'admin.access', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'media.view', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'media.view']);

    MediaAsset::factory()->create([
        'title' => 'Alpha Regimental Flag',
        'original_name' => 'alpha_flag.jpg',
        'public_filename' => 'alpha_flag.jpg',
        'type' => MediaType::Image->value,
        'visibility' => MediaVisibility::Public->value,
    ]);

    MediaAsset::factory()->create([
        'title' => 'Beta Memorial Parade',
        'original_name' => 'beta_memorial.jpg',
        'public_filename' => 'beta_memorial.jpg',
        'type' => MediaType::Image->value,
        'visibility' => MediaVisibility::Public->value,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('admin.media.recent-images', ['q' => 'Flag']));

    $response->assertOk()
        ->assertJson(['success' => true]);

    $images = $response->json('images');
    expect(count($images))->toBeGreaterThanOrEqual(1);
    expect($images[0]['title'])->toContain('Flag');
});

it('reuses the same uploaded image even when it has a different filename', function (): void {
    Storage::fake('public');
    Permission::firstOrCreate(['name' => 'admin.access', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'media.upload', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'media.upload']);
    $first = $this->actingAs($user)->postJson(route('admin.media.quick-upload'), [
        'file' => UploadedFile::fake()->image('original.png', 100, 80),
        'title' => 'Original image',
        'alt_text' => 'Original library description',
    ])->assertOk()->assertJsonPath('reused', false);
    $media = MediaAsset::query()->sole();
    $files = Storage::disk('public')->allFiles();
    $bytes = Storage::disk('public')->get($media->path);

    $this->postJson(route('admin.media.quick-upload'), [
        'file' => UploadedFile::fake()->createWithContent('renamed.png', $bytes),
        'title' => 'Another page image',
        'alt_text' => 'Description on this page',
    ])->assertOk()->assertJsonPath('reused', true)
        ->assertJsonPath('id', $media->id)->assertJsonPath('path', $first->json('path'));

    expect(MediaAsset::query()->count())->toBe(1)
        ->and(Storage::disk('public')->allFiles())->toBe($files)
        ->and($media->fresh()->title)->toBe('Original image')
        ->and($media->fresh()->alt_text)->toBe('Original library description');
});

it('keeps private files out of the image picker and public duplicate reuse', function (): void {
    Storage::fake('public');
    Storage::fake('local');
    foreach (['admin.access', 'media.upload', 'media.view'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'media.upload', 'media.view']);
    $file = UploadedFile::fake()->image('private.png', 100, 80);
    $private = app(MediaUploadService::class)->upload($file, MediaType::Image, MediaVisibility::Internal, $user);
    $misconfigured = MediaAsset::factory()->create(['type' => MediaType::Image, 'source' => MediaSource::Upload, 'visibility' => MediaVisibility::Public, 'disk' => 'local']);
    $this->actingAs($user)->getJson(route('admin.media.recent-images'))->assertOk()->assertJsonCount(0, 'images');
    $this->postJson(route('admin.media.quick-upload'), [
        'file' => UploadedFile::fake()->createWithContent('public.png', Storage::disk('local')->get($private->path)),
    ])->assertOk()->assertJsonPath('reused', false);
    expect(MediaAsset::query()->count())->toBe(3);
    $ids = $this->getJson(route('admin.media.recent-images'))->assertOk()->json('images.*.id');
    expect($ids)->not->toContain($private->id, $misconfigured->id)->toHaveCount(1);
});
