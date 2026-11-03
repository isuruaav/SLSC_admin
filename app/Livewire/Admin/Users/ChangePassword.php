<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        Gate::authorize('admin.access');
    }

    public function updatePassword(): void
    {
        Gate::authorize('admin.access');
        $actor = Auth::user();
        abort_unless($actor instanceof User && $actor->is_active, 403);

        try {
            $this->validate([
                'current_password' => ['required', 'string', 'current_password:web'],
                'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::min(12)->mixedCase()->numbers()->symbols()],
            ], [
                'current_password.current_password' => 'Your current password is incorrect.',
            ]);

            $currentSession = request()->hasSession() ? request()->session()->getId() : null;
            $user = DB::transaction(function () use ($actor, $currentSession): User {
                $user = User::query()->lockForUpdate()->findOrFail($actor->id);
                abort_unless($user->is_active, 403);
                Gate::forUser($user)->authorize('admin.access');
                if (! Hash::check($this->current_password, $user->password)) {
                    throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
                }

                $user->forceFill([
                    'password' => $this->password,
                    'remember_token' => Str::random(60),
                    'updated_by' => $user->id,
                ])->save();

                $sessions = DB::table('sessions')->where('user_id', $user->id);
                if ($currentSession !== null) {
                    $sessions->where('id', '!=', $currentSession);
                }
                $sessions->delete();
                PasswordBroker::broker()->deleteToken($user);

                app(AuditLogger::class)->log(
                    event: 'users.password-changed',
                    description: 'User changed their own password.',
                    actor: $user,
                    subject: $user,
                    newValues: ['other_sessions_terminated' => true, 'password_reset_token_invalidated' => true],
                );

                return $user;
            }, 3);
        } catch (ValidationException $exception) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $exception;
        }

        Auth::setUser($user);
        if (request()->hasSession()) {
            session()->put('password_hash_web', $user->getAuthPassword());
            session()->regenerate(true);
        }
        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('status', 'Your password was changed successfully. Other active sessions were signed out.');
    }

    public function render(): View
    {
        Gate::authorize('admin.access');

        return view('livewire.admin.users.change-password')
            ->layout('components.layouts.admin', ['title' => 'Change Password']);
    }
}
