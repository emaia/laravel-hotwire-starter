<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

final class TwoFactorCredentials extends AccountCredentials
{
    public function __construct(private readonly TwoFactorAuthenticationProvider $provider, Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Consume a proof while the caller holds the account row lock.
     *
     * @throws ValidationException
     */
    public function verify(User $user, Request $request): void
    {
        $input = $request->validate([
            'code' => ['nullable', 'string', 'regex:/\A[0-9]{6}\z/', 'prohibits:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'max:100', 'prohibits:code'],
        ]);
        if (filled($input['recovery_code'] ?? null) && $user->two_factor_recovery_codes !== null) {
            $codes = $user->recoveryCodes();
            $remaining = array_values(array_filter($codes, fn (string $code): bool => ! hash_equals($code, $input['recovery_code'])));
            if (count($remaining) < count($codes)) {
                $user->forceFill([
                    'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($remaining, JSON_THROW_ON_ERROR)),
                ])->save();

                return;
            }
        } elseif (filled($input['code'] ?? null) && $user->two_factor_secret !== null
            && $this->provider->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $input['code'])) {
            return;
        }

        throw ValidationException::withMessages(['code' => __('The authentication or recovery code is invalid.')]);
    }
}
