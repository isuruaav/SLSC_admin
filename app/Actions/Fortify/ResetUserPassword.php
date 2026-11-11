<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        if ($user->getAttribute('invited_at') !== null && $user->getAttribute('invitation_accepted_at') === null) {
            $user->forceFill(['email_verified_at' => now(), 'invitation_accepted_at' => now()]);
        }
        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
