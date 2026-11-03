<?php

namespace App\Services;

use App\Models\User;
use App\Support\UserManagementRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;

final class UserDeletionService
{
    public function delete(User $actor, int $userId): User
    {
        Gate::forUser($actor)->authorize('users.delete');

        return DB::transaction(function () use ($actor, $userId): User {
            UserManagementRules::lockSuperAdministrators();
            $target = User::query()->with('roles')->lockForUpdate()->findOrFail($userId);
            UserManagementRules::ensureDeletionAllowed($actor, $target);

            app(AuditLogger::class)->log(
                event: 'users.deleted',
                description: 'Administrator deleted a user account. Existing content was retained.',
                actor: $actor,
                subject: $target,
                oldValues: [
                    'name' => $target->name,
                    'email' => $target->email,
                    'roles' => $target->roles->pluck('name')->all(),
                    'is_active' => $target->is_active,
                ],
                newValues: ['sessions_terminated' => true, 'content_retained' => true],
            );

            DB::table('sessions')->where('user_id', $target->id)->delete();
            Password::broker()->deleteToken($target);
            $target->delete();

            return $target;
        }, 3);
    }
}
