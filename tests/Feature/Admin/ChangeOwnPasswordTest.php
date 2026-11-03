<?php

use App\Livewire\Admin\Users\ChangePassword;
use App\Livewire\Admin\Users\UserEdit;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create(['password' => 'CurrentAdminPassword123!', 'remember_token' => 'old-remember-token']);
    $this->admin->assignRole('Super Administrator');
    $this->actingAs($this->admin);
});

it('makes own password changes available from the admin menu and own edit page', function (): void {
    $this->get(route('admin.account.password'))->assertOk()->assertSee('Your account password')->assertSee('Current password');
    $this->get(route('admin.users.index'))->assertOk()->assertSee(route('admin.account.password'));
    Livewire::test(UserEdit::class, ['user' => $this->admin])->assertSee('Change My Password')->assertDontSee('Secure Password Reset');
});

it('changes only the signed-in account and clears sensitive inputs', function (): void {
    $other = User::factory()->create(['password' => 'OtherAccountPassword123!']);
    Livewire::test(ChangePassword::class)->set('current_password', 'CurrentAdminPassword123!')
        ->set('password', 'ChangedAccountPassword456!')->set('password_confirmation', 'ChangedAccountPassword456!')
        ->call('updatePassword')->assertHasNoErrors()
        ->assertSet('current_password', '')->assertSet('password', '')->assertSet('password_confirmation', '');

    expect(Hash::check('ChangedAccountPassword456!', $this->admin->fresh()->password))->toBeTrue()
        ->and(Hash::check('OtherAccountPassword123!', $other->fresh()->password))->toBeTrue()
        ->and($this->admin->fresh()->remember_token)->not->toBe('old-remember-token');
    $this->assertAuthenticatedAs($this->admin);
    $audit = AuditLog::query()->where('event', 'users.password-changed')->sole();
    expect($audit->actor_id)->toBe($this->admin->id)
        ->and(json_encode($audit->toArray()))->not->toContain('CurrentAdminPassword123!', 'ChangedAccountPassword456!', 'old-remember-token');
});

it('keeps the current browser signed in and invalidates other sessions and reset links', function (): void {
    // Exercise the real web middleware: Livewire's unit helper disables sessions.
    config()->set('session.driver', 'database');
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');
    Auth::forgetGuards();
    Auth::guard('web')->login($this->admin);
    session()->save();
    $currentSession = session()->getId();
    $this->withCookie(config('session.cookie'), $currentSession);
    $page = $this->get(route('admin.account.password'))->assertOk();
    preg_match_all('/wire:snapshot="([^"]+)"/', $page->getContent(), $matches);
    $snapshot = null;
    foreach ($matches[1] as $encoded) {
        $candidate = html_entity_decode($encoded, ENT_QUOTES, 'UTF-8');
        $data = json_decode($candidate, true);
        if (array_key_exists('current_password', $data['data'] ?? [])) {
            $snapshot = $candidate;
            break;
        }
    }
    expect($snapshot)->toBeString();
    DB::table('sessions')->insert(['id' => 'other-password-session', 'user_id' => $this->admin->id, 'payload' => '', 'last_activity' => time()]);
    Password::broker()->createToken($this->admin);
    $this->admin->forceFill(['two_factor_secret' => encrypt('existing-2fa-secret')])->save();
    $twoFactor = $this->admin->fresh()->getRawOriginal('two_factor_secret');

    $this->withHeaders(['X-Livewire' => '1'])->postJson(app('livewire')->getUpdateUri(), [
        'components' => [[
            'snapshot' => $snapshot,
            'updates' => ['current_password' => 'CurrentAdminPassword123!', 'password' => 'ChangedAccountPassword456!', 'password_confirmation' => 'ChangedAccountPassword456!'],
            'calls' => [['path' => '', 'method' => 'updatePassword', 'params' => []]],
        ]],
    ])->assertOk();

    $this->assertAuthenticatedAs($this->admin);
    $this->assertDatabaseMissing('sessions', ['id' => 'other-password-session']);
    $newSession = session()->getId();
    expect($newSession)->not->toBe($currentSession);
    $this->assertDatabaseMissing('sessions', ['id' => $currentSession]);
    $this->assertDatabaseHas('sessions', ['id' => $newSession, 'user_id' => $this->admin->id]);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $this->admin->email]);
    expect($this->admin->fresh()->getRawOriginal('two_factor_secret'))->toBe($twoFactor);
    expect(Hash::check('ChangedAccountPassword456!', $this->admin->fresh()->password))->toBeTrue();

    $this->withCookie(config('session.cookie'), $newSession)->withHeaders(['X-Livewire' => '', 'Accept' => 'text/html']);
    Auth::forgetGuards();
    $this->get(route('admin.account.password'))->assertOk();
});

it('rejects incorrect current passwords weak passwords and mismatched confirmation', function (string $current, string $password, string $confirmation, string $error): void {
    Livewire::test(ChangePassword::class)->set('current_password', $current)->set('password', $password)
        ->set('password_confirmation', $confirmation)->call('updatePassword')->assertHasErrors($error)
        ->assertSet('current_password', '')->assertSet('password', '')->assertSet('password_confirmation', '');
    expect(Hash::check('CurrentAdminPassword123!', $this->admin->fresh()->password))->toBeTrue();
    expect(AuditLog::query()->where('event', 'users.password-changed')->count())->toBe(0);
})->with([
    ['IncorrectCurrentPassword123!', 'ChangedAccountPassword456!', 'ChangedAccountPassword456!', 'current_password'],
    ['CurrentAdminPassword123!', 'short', 'short', 'password'],
    ['CurrentAdminPassword123!', 'ChangedAccountPassword456!', 'DifferentPassword789!', 'password'],
    ['CurrentAdminPassword123!', 'CurrentAdminPassword123!', 'CurrentAdminPassword123!', 'password'],
]);

it('does not require permission to reset other users passwords to change your own password', function (): void {
    $editor = User::factory()->create(['password' => 'EditorCurrentPassword123!']);
    $editor->assignRole('Content Editor');
    $this->actingAs($editor);
    Livewire::test(ChangePassword::class)->set('current_password', 'EditorCurrentPassword123!')
        ->set('password', 'EditorUpdatedPassword456!')->set('password_confirmation', 'EditorUpdatedPassword456!')
        ->call('updatePassword')->assertHasNoErrors();
    expect(Hash::check('EditorUpdatedPassword456!', $editor->fresh()->password))->toBeTrue();
});

it('protects the route from guests and users without administrator access', function (): void {
    auth()->logout();
    $this->get(route('admin.account.password'))->assertRedirect(route('login'));
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->get(route('admin.account.password'))->assertForbidden();
});
