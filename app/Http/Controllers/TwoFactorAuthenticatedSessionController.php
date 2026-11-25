<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\TwoFactorCredentials;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\TwoFactorChallengeViewResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController as FortifyController;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;
use Throwable;

final class TwoFactorAuthenticatedSessionController extends FortifyController
{
    public function __construct(StatefulGuard $guard, private readonly TwoFactorCredentials $credentials)
    {
        parent::__construct($guard);
    }

    /** Return expired challenges to login without losing the intended destination. */
    public function create(TwoFactorLoginRequest $request): TwoFactorChallengeViewResponse
    {
        if (! $request->hasChallengedUser()) {
            throw new HttpResponseException(redirect()->route('login')->with('status', __('Please sign in to start a new authentication challenge.')));
        }

        return app(TwoFactorChallengeViewResponse::class);
    }

    /** Serialize proof consumption and session creation against credential changes. */
    public function store(TwoFactorLoginRequest $request): TwoFactorLoginResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $user = $request->challengedUser();
                $this->credentials->verify($user, $request);
                event(new ValidTwoFactorAuthenticationCodeProvided($user));
                $this->guard->login($user, $request->remember());
            });
        } catch (Throwable $exception) {
            if ($this->guard->check()) {
                Auth::logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            throw $exception;
        }
        $request->session()->forget('login');
        $request->session()->forget('auth.password_confirmed_at');
        $request->session()->regenerate();

        return app(TwoFactorLoginResponse::class);
    }
}
