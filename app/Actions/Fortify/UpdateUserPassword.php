<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private readonly AccountCredentials $credentials) {}

    /**
     * Change the current account's password and revoke its other sessions.
     *
     * @param  User  $user
     * @param  array<string, string>  $input
     */
    public function update(mixed $user, array $input): void
    {
        $this->credentials->change($user, function (User $current) use ($input): void {
            Validator::make($input, [
                'current_password' => ['required', 'string', 'max:1024', 'current_password:web'],
                'password' => $this->passwordRules(),
            ])->validate();

            $current->forceFill(['password' => $input['password']])->save();
            $this->credentials->revokeOtherAccess($current);
        }, 'current_password');
    }
}
