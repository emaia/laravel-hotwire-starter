<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication as FortifyConfirm;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

final class ConfirmTwoFactorAuthentication extends FortifyConfirm
{
    public function __construct(TwoFactorAuthenticationProvider $provider, private readonly TwoFactorCredentials $credentials)
    {
        parent::__construct($provider);
    }

    /**
     * Activate the authenticator and revoke previous access atomically.
     *
     * @param  User  $user
     * @param  string  $code
     */
    public function __invoke(mixed $user, mixed $code): void
    {
        Validator::make(['code' => $code], ['code' => ['required', 'string', 'regex:/\A[0-9]{6}\z/']])->validate();
        $this->credentials->change($user, function (User $current) use ($code): void {
            if ($current->two_factor_confirmed_at !== null) {
                throw ValidationException::withMessages(['code' => __('Two-factor authentication is already confirmed.')]);
            }
            try {
                parent::__invoke($current, $code);
            } catch (ValidationException $exception) {
                throw $exception->errorBag('default');
            }
            $this->credentials->revokeOtherAccess($current);
        });
    }
}
