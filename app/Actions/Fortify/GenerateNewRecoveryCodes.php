<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes as FortifyGenerate;

final class GenerateNewRecoveryCodes extends FortifyGenerate
{
    public function __construct(private readonly TwoFactorCredentials $credentials, private readonly Request $request) {}

    /**
     * Require possession before invalidating the previous recovery set.
     *
     * @param  User  $user
     */
    public function __invoke(mixed $user): void
    {
        $this->credentials->change($user, function (User $current): void {
            if ($current->two_factor_confirmed_at === null) {
                throw ValidationException::withMessages(['code' => __('Confirm the authenticator before generating recovery codes.')]);
            }
            $this->credentials->verify($current, $this->request);
            parent::__invoke($current);
            $this->credentials->revokeOtherAccess($current);
        });
    }
}
