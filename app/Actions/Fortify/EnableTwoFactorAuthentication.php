<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication as FortifyEnable;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

final class EnableTwoFactorAuthentication extends FortifyEnable
{
    public function __construct(TwoFactorAuthenticationProvider $provider, private readonly TwoFactorCredentials $credentials)
    {
        parent::__construct($provider);
    }

    /**
     * Prepare setup without overwriting a confirmed authenticator.
     *
     * @param  User  $user
     */
    public function __invoke(mixed $user, mixed $force = false): void
    {
        $this->credentials->change($user, function (User $current) use ($force): void {
            if ($current->two_factor_confirmed_at !== null) {
                throw ValidationException::withMessages(['code' => __('Disable the current authenticator before configuring another.')]);
            }
            parent::__invoke($current, $force);
        });
    }
}
