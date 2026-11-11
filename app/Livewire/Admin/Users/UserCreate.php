<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\AuditLogger;
use App\Support\UserManagementRules;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class UserCreate extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = '';

    public bool $isActive = true;

    public string $password = '';

    public string $password_confirmation = '';

    public bool $inviteByLink = false;

    #[Locked]
    public string $invitationUrl = '';

    public function mount(): void
    {
        Gate::authorize('users.create');
        Gate::authorize('users.assign-role');
    }

    public function save(): void
    {
        Gate::authorize('users.create');
        Gate::authorize('users.assign-role');

        $actor = $this->actor();
        $this->normaliseInput();
        $this->validate();
        UserManagementRules::ensureRoleCanBeAssigned($actor, $this->role);

        $user = DB::transaction(function () use ($actor): User {
            UserManagementRules::ensureRoleCanBeAssigned($actor, $this->role);

            $user = User::query()->create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->inviteByLink ? Str::random(64) : $this->password,
                'is_active' => $this->isActive,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            // Preserve the existing administrator-created email verification policy.
            $user->forceFill(['email_verified_at' => $this->inviteByLink ? null : Carbon::now(), 'invited_at' => $this->inviteByLink ? now() : null])->saveQuietly();
            $user->syncRoles([$this->role]);

            app(AuditLogger::class)->log(
                event: 'users.created',
                description: 'Administrator account created.',
                actor: $actor,
                subject: $user,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $this->role,
                    'is_active' => (bool) $user->is_active,
                    'email_verified' => $user->email_verified_at !== null,
                ],
            );

            return $user;
        });

        $this->reset(['password', 'password_confirmation']);
        if ($this->inviteByLink) {
            $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
            $this->invitationUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
            session()->flash('status', 'Invitation created. Share the single-use link privately with this user.');

            return;
        }
        session()->flash('status', "{$user->name}'s account was created successfully.");

        if (Gate::allows('users.update') && UserManagementRules::canManageTarget($actor, $user)) {
            $this->redirectRoute('admin.users.edit', ['user' => $user->id], navigate: true);

            return;
        }

        if (Gate::allows('users.view')) {
            $this->redirectRoute('admin.users.index', navigate: true);

            return;
        }

        $this->reset(['name', 'email', 'role', 'isActive']);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'string', Rule::in(UserManagementRules::assignableRoleNames($this->actor()))],
            'isActive' => ['boolean'],
            'inviteByLink' => ['boolean'],
            'password' => [Rule::excludeIf($this->inviteByLink), 'required', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ];
    }

    public function render(): View
    {
        Gate::authorize('users.create');
        Gate::authorize('users.assign-role');
        $roles = UserManagementRules::assignableRoleNames($this->actor());

        return view('livewire.admin.users.user-create', compact('roles'))
            ->layout('components.layouts.admin', ['title' => 'Create User']);
    }

    private function actor(): User
    {
        $actor = Auth::user();
        abort_unless($actor instanceof User, 403);

        return $actor;
    }

    private function normaliseInput(): void
    {
        $this->name = trim($this->name);
        $this->email = mb_strtolower(trim($this->email));
        $this->role = trim($this->role);
    }
}
