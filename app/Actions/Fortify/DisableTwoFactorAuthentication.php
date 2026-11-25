<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication as FortifyDisable;

final class DisableTwoFactorAuthentication extends FortifyDisable
{
    public function __construct(private readonly TwoFactorCredentials $credentials, private readonly Request $request) {}

    /**
     * Cancel pending setup or require possession to remove an active factor.
     *
     * @param  User  $user
     */
    public function __invoke(mixed $user): void
    {
        $this->credentials->change($user, function (User $current): void {
            if ($current->two_factor_confirmed_at === null) {
                parent::__invoke($current);

                return;
            }
            $this->credentials->verify($current, $this->request);
            parent::__invoke($current);
            $this->credentials->revokeOtherAccess($current);
        });
    }
}
