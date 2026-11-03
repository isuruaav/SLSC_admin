<?php

use App\Livewire\Admin\Users\UserCreate;
use App\Livewire\Admin\Users\UserIndex;
use App\Models\Page;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create(['password' => 'CurrentAdminPassword123!']);
    $this->admin->assignRole('Super Administrator');
    $this->actingAs($this->admin);
});

it('deletes an account created through the user form and shows a confirmation action', function (): void {
    Livewire::test(UserCreate::class)->set('name', 'Created Test Operator')->set('email', 'created.operator@example.com')
        ->set('role', 'Content Editor')->set('password', 'NewOperatorPassword123!')
        ->set('password_confirmation', 'NewOperatorPassword123!')->call('save')->assertHasNoErrors();
    $target = User::query()->where('email', 'created.operator@example.com')->sole();

    Livewire::test(UserIndex::class)->assertSee('wire:click="deleteUser('.$target->id.')"', false)
        ->assertSee('This cannot be undone.')
        ->assertDontSee('wire:click="deleteUser('.$this->admin->id.')"', false)
        ->call('deleteUser', $target->id)->assertHasNoErrors()->assertDontSee('created.operator@example.com');

    expect($target->fresh())->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['event' => 'users.deleted', 'subject_id' => $target->id, 'actor_id' => $this->admin->id]);
});

it('retains authored pages and audit history while removing login sessions credentials and grants', function (): void {
    $target = User::factory()->create();
    $target->assignRole('Content Editor');
    $target->givePermissionTo('media.upload');
    $page = Page::factory()->published()->create(['created_by' => $target->id, 'updated_by' => $target->id]);
    $history = app(AuditLogger::class)->log('pages.created', 'Existing author history', actor: $target, subject: $page);
    Password::broker()->createToken($target);
    DB::table('sessions')->insert([
        ['id' => 'deleted-user-session', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()],
        ['id' => 'retained-admin-session', 'user_id' => $this->admin->id, 'payload' => '', 'last_activity' => time()],
    ]);
    DB::table('passkeys')->insert(['user_id' => $target->id, 'name' => 'Test passkey', 'credential_id' => 'delete-test-key', 'credential' => '{}']);

    Livewire::test(UserIndex::class)->call('deleteUser', $target->id)->assertHasNoErrors();

    expect($page->fresh())->not->toBeNull()->and($page->fresh()->created_by)->toBeNull()
        ->and($history->fresh()->actor_id)->toBeNull()->and($history->fresh()->actor_name)->toBe($target->name);
    $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
    $this->assertDatabaseHas('sessions', ['id' => 'retained-admin-session']);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $target->email]);
    $this->assertDatabaseMissing('passkeys', ['user_id' => $target->id]);
    $this->assertDatabaseMissing('model_has_roles', ['model_id' => $target->id, 'model_type' => $target->getMorphClass()]);
    $this->assertDatabaseMissing('model_has_permissions', ['model_id' => $target->id, 'model_type' => $target->getMorphClass()]);
});

it('requires delete permission even when the action is called directly', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('Site Administrator');
    $target = User::factory()->create();
    $target->assignRole('Content Editor');
    $this->actingAs($actor);

    Livewire::test(UserIndex::class)->assertDontSee('wire:click="deleteUser(', false)
        ->call('deleteUser', $target->id)->assertForbidden();
    expect($target->fresh())->not->toBeNull();
});

it('does not allow an administrator to delete a protected account with stronger access', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('Site Administrator');
    $actor->givePermissionTo('users.delete');
    $this->actingAs($actor);

    Livewire::test(UserIndex::class)->call('deleteUser', $this->admin->id)->assertForbidden();
    expect($this->admin->fresh())->not->toBeNull();
});

it('prevents deletion of the current account and the last active super administrator', function (): void {
    Livewire::test(UserIndex::class)->call('deleteUser', $this->admin->id)->assertHasErrors('user');
    $inactiveAdmin = User::factory()->create(['is_active' => false]);
    $inactiveAdmin->assignRole('Super Administrator');
    $this->actingAs($inactiveAdmin);
    Livewire::test(UserIndex::class)->call('deleteUser', $this->admin->id)->assertHasErrors('user');
    expect($this->admin->fresh())->not->toBeNull();
});

it('allows a super administrator to delete another super administrator when an active owner remains', function (): void {
    $target = User::factory()->create();
    $target->assignRole('Super Administrator');
    Livewire::test(UserIndex::class)->call('deleteUser', $target->id)->assertHasNoErrors();
    expect(User::role('Super Administrator')->where('is_active', true)->count())->toBe(1);
});

it('also protects a super administrator from deletion through the personal profile form', function (): void {
    Livewire::test('settings.delete-user-form')->set('password', 'CurrentAdminPassword123!')
        ->call('deleteUser')->assertHasErrors('password');
    expect($this->admin->fresh())->not->toBeNull();
});
