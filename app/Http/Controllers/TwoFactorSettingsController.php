<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

final class TwoFactorSettingsController extends Controller
{
    /** Render setup secrets only while the authenticator is unconfirmed. */
    public function show(#[CurrentUser] User $user): View
    {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()), 404);
        $pending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;

        return view('auth.two-factor.settings', [
            'user' => $user,
            'pending' => $pending,
            'secret' => $pending ? Fortify::currentEncrypter()->decrypt($user->two_factor_secret) : null,
            'qrCode' => $pending ? $user->twoFactorQrCodeSvg() : null,
        ]);
    }

    /** Reveal recovery codes only after confirmed enrollment and a fresh password. */
    public function recovery(#[CurrentUser] User $user): View
    {
        abort_unless(Features::enabled(Features::twoFactorAuthentication()) && $user->two_factor_confirmed_at !== null, 404);

        return view('auth.two-factor.recovery', ['codes' => $user->recoveryCodes()]);
    }
}
