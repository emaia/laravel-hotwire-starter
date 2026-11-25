<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as FortifyRedirect;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

final class RedirectIfTwoFactorAuthenticatable extends FortifyRedirect
{
    /**
     * Serialize the factor decision with password-only session creation.
     *
     * @param  Request  $request
     * @param  callable  $next
     */
    public function handle(mixed $request, mixed $next): mixed
    {
        return DB::transaction(function () use ($request, $next): mixed {
            User::query()->where(Fortify::username(), $request->input(Fortify::username()))->lockForUpdate()->first();

            return parent::handle($request, $next);
        });
    }

    /**
     * Bind a five-minute challenge to the exact password and credential generation.
     *
     * @param  Request  $request
     * @param  User  $user
     */
    protected function twoFactorChallengeResponse(mixed $request, mixed $user): Response
    {
        $current = User::query()->lockForUpdate()->find($user->id);
        if ($current === null || $current->auth_version !== $user->auth_version || $current->password !== $user->password) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }
        $request->session()->forget(['login', 'auth.password_confirmed_at']);
        $request->session()->regenerate();
        $request->session()->put([
            'login.auth_version' => $current->auth_version,
            'login.password_hash' => hash('sha256', $current->password),
            'login.expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        return parent::twoFactorChallengeResponse($request, $current);
    }
}
