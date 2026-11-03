<?php

use App\Livewire\Admin\Roles\RolePermissionIndex;
use App\Livewire\Admin\Users\UserCreate;
use App\Livewire\Admin\Users\UserEdit;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\DefaultRoleService;
use App\Support\RolePresets;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function rolePresetOwner(): User
{
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole(Role::findOrCreate('Super Administrator', 'web'));

    return $owner;
}

test('recommended role setup creates six useful roles with distinct access', function (): void {
    $this->artisan('roles:setup')->assertSuccessful();

    expect(Role::query()->count())->toBe(6)
        ->and(Permission::query()->count())->toBe(63);
    $publisher = Role::findByName('Publisher', 'web');
    $editor = Role::findByName('Content Editor', 'web');
    $media = Role::findByName('Media Operator', 'web');
    $auditor = Role::findByName('Auditor', 'web');
    expect($publisher->hasPermissionTo('news.publish'))->toBeTrue()
        ->and($editor->hasPermissionTo('news.submit'))->toBeTrue()
        ->and($editor->hasPermissionTo('media.upload'))->toBeTrue()
        ->and($editor->hasPermissionTo('news.publish'))->toBeFalse()
        ->and($media->hasPermissionTo('media.replace'))->toBeTrue()
        ->and($media->hasPermissionTo('news.create'))->toBeFalse()
        ->and($auditor->hasPermissionTo('audit.view'))->toBeTrue()
        ->and($auditor->hasPermissionTo('pages.update'))->toBeFalse()
        ->and(Role::findByName('Site Administrator', 'web')->hasPermissionTo('roles.manage'))->toBeFalse();
});

test('setup preserves customized roles direct grants users and assignments on repeated runs', function (): void {
    $existing = Role::findOrCreate('Publisher', 'web');
    $customPermission = Permission::findOrCreate('special-inspection.view', 'web');
    $existing->givePermissionTo($customPermission);
    $custom = Role::findOrCreate('My existing team', 'web');
    $user = User::factory()->create();
    $user->assignRole([$existing, $custom]);
    $user->givePermissionTo($customPermission);

    app(DefaultRoleService::class)->install();
    $createdLogs = AuditLog::query()->where('event', 'roles.created')->count();
    app(DefaultRoleService::class)->install();

    expect($existing->fresh()->permissions->pluck('name')->all())->toBe(['special-inspection.view'])
        ->and($user->fresh()->getRoleNames()->sort()->values()->all())->toBe(['My existing team', 'Publisher'])
        ->and($user->fresh()->permissions->pluck('name')->all())->toBe(['special-inspection.view'])
        ->and(User::query()->count())->toBe(1)
        ->and(Role::query()->count())->toBe(7)
        ->and(AuditLog::query()->where('event', 'roles.created')->count())->toBe($createdLogs);
});

test('owner can create a custom role from a recommended template', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $owner = rolePresetOwner();

    Livewire::actingAs($owner)->test(RolePermissionIndex::class)
        ->set('newRoleName', 'News team')
        ->set('newRoleTemplate', 'Content Editor')
        ->call('createRole')->assertHasNoErrors()->assertDispatched('role-editor-reset');

    expect(Role::findByName('News team', 'web')->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect(RolePresets::all()['Content Editor']['permissions'])->sort()->values()->all());
    $this->assertDatabaseHas('audit_logs', ['event' => 'roles.created', 'actor_id' => $owner->id]);
});

test('blank custom roles remain empty and super administrator is not a copy template', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $owner = rolePresetOwner();

    Livewire::actingAs($owner)->test(RolePermissionIndex::class)
        ->set('newRoleName', 'Future staff')->set('newRoleTemplate', 'blank')
        ->call('createRole')->assertHasNoErrors();
    expect(Role::findByName('Future staff', 'web')->permissions)->toHaveCount(0);

    Livewire::actingAs($owner)->test(RolePermissionIndex::class)
        ->set('newRoleName', 'Forged owner')->set('newRoleTemplate', 'Super Administrator')
        ->call('createRole')->assertHasErrors('newRoleTemplate');
    expect(Role::query()->where('name', 'Forged owner')->exists())->toBeFalse();
});

test('selecting actions adds prerequisites and removing view removes dependent actions', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $role = Role::findOrCreate('New reporting role', 'web');
    $component = Livewire::actingAs(rolePresetOwner())->test(RolePermissionIndex::class)
        ->call('selectRole', $role->id)
        ->call('togglePermission', 'users.create')
        ->assertSet('selectedPermissions', ['admin.access', 'users.assign-role', 'users.create', 'users.view']);

    expect($role->fresh()->permissions)->toHaveCount(0);
    $component->call('togglePermission', 'users.reset-password')
        ->assertSet('selectedPermissions', ['admin.access', 'users.assign-role', 'users.create', 'users.reset-password', 'users.update', 'users.view'])
        ->call('savePermissions')->assertHasNoErrors()
        ->call('togglePermission', 'users.view')->assertSet('selectedPermissions', ['admin.access'])
        ->call('togglePermission', 'admin.access')->assertSet('selectedPermissions', []);
});

test('group selection and recommended templates are staged until saved and can be discarded', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $role = Role::findOrCreate('News review role', 'web');
    $component = Livewire::actingAs(rolePresetOwner())->test(RolePermissionIndex::class)
        ->call('selectRole', $role->id)->call('togglePermissionGroup', 'news')
        ->assertHasNoErrors()->assertDispatched('role-editor-changed');
    expect($component->get('selectedPermissions'))->toContain('admin.access', 'news.view', 'news.publish');
    expect($role->fresh()->permissions)->toHaveCount(0);
    $component->call('togglePermissionGroup', 'news')->assertSet('selectedPermissions', ['admin.access'])
        ->set('permissionTemplate', 'Auditor')->call('applyTemplate')->assertHasNoErrors();
    expect($component->get('selectedPermissions'))->toContain('audit.view')->not->toContain('news.publish');
    $component->call('discardChanges')->assertSet('selectedPermissions', [])->assertDispatched('role-editor-reset');
});

test('super administrator permissions cannot be altered through template or toggle actions', function (string $action, array $arguments): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $role = Role::findByName('Super Administrator', 'web');
    Livewire::actingAs(rolePresetOwner())->test(RolePermissionIndex::class)
        ->call('selectRole', $role->id)->call($action, ...$arguments)->assertHasErrors('role');
})->with([
    ['togglePermission', ['news.view']],
    ['togglePermissionGroup', ['news']],
    ['applyTemplate', []],
]);

test('delegated role inspection cannot invoke new role management actions', function (string $action, array $arguments): void {
    $actor = User::factory()->create();
    $actor->givePermissionTo(Permission::findOrCreate('roles.manage', 'web'));
    Role::findOrCreate('Inspection target', 'web');
    Livewire::actingAs($actor)->test(RolePermissionIndex::class)->call($action, ...$arguments)->assertForbidden();
})->with([
    ['installRecommendedRoles', []],
    ['togglePermission', ['roles.manage']],
    ['togglePermissionGroup', ['roles']],
    ['applyTemplate', []],
]);

test('role assignment screens explain actual access and protect read-only role selectors', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $owner = rolePresetOwner();
    Livewire::actingAs($owner)->test(UserCreate::class)->set('role', 'Content Editor')
        ->assertSee('recommended for most staff')->assertSee('View allowed actions')->assertSee('Submit for review');

    $actor = User::factory()->create();
    $actor->givePermissionTo(Permission::findByName('users.update', 'web'));
    $target = User::factory()->create();
    $target->assignRole(Role::findOrCreate('Empty account role', 'web'));
    Livewire::actingAs($actor)->test(UserEdit::class, ['user' => $target])
        ->assertSee('cannot change their role')->assertSee('no admin workspace access');
});

test('permissions page renders friendly labels and can install only missing default roles', function (): void {
    $owner = rolePresetOwner();
    $component = Livewire::actingAs($owner)->test(RolePermissionIndex::class)
        ->assertSee('Add recommended roles')->call('installRecommendedRoles')->assertHasNoErrors()
        ->call('selectRole', Role::findByName('Content Editor', 'web')->id)
        ->assertSee('Submit for review')->assertSee('Find access')->assertSee('Create a custom role')
        ->assertDontSeeHtml('>news.submit<');
    if (getenv('ROLE_UI_WRITE_FIXTURES') === '1') {
        file_put_contents(storage_path('framework/testing/role-permissions.html'), $component->html());
    }
    expect($owner->fresh()->getRoleNames()->all())->toBe(['Super Administrator']);
});
