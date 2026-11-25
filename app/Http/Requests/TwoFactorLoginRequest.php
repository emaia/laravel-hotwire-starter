<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest as FortifyRequest;

final class TwoFactorLoginRequest extends FortifyRequest
{
    /** Resolve only the account and credential generation bound to this challenge. */
    public function challengedUser(): User
    {
        $id = $this->session()->get('login.id');
        $user = null;
        if ((is_int($id) || is_string($id)) && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
            $user = User::query()->lockForUpdate()->find($id);
        }
        if ($user === null || $this->session()->get('login.expires_at', 0) <= now()->timestamp
            || $this->session()->get('login.auth_version') !== $user->auth_version
            || ! hash_equals(hash('sha256', $user->password), $this->session()->get('login.password_hash', ''))
            || $user->two_factor_secret === null || $user->two_factor_confirmed_at === null) {
            $this->session()->forget('login');
            throw ValidationException::withMessages(['code' => __('The challenge expired or your credentials changed. Please sign in again.')]);
        }

        return $user;
    }

    /** Reject missing or stale challenges before rendering the form. */
    public function hasChallengedUser(): bool
    {
        try {
            $this->challengedUser();

            return true;
        } catch (ValidationException) {
            return false;
        }
    }
}
