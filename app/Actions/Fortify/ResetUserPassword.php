<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private readonly AccountCredentials $credentials) {}

    /**
     * Reset the password without bypassing or removing the existing second factor.
     *
     * @param  User  $user
     * @param  array<string, string>  $input
     */
    public function reset(mixed $user, array $input): void
    {
        Validator::make($input, ['password' => $this->passwordRules()])->validate();
        $this->credentials->change($user, function (User $current) use ($input): void {
            $current->forceFill(['password' => $input['password']])->save();
            $this->credentials->revokeOtherAccess($current);
        }, 'email');
    }
}
