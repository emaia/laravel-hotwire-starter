<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\ProfileInformationController;
use App\Http\Controllers\TwoFactorAuthenticatedSessionController;
use App\Http\Requests\TwoFactorLoginRequest;
use App\Http\Responses\TwoFactorConfirmedResponse;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bind the generic credential and second-factor protections.
     */
    public function register(): void
    {
        $this->app->bind(EnableTwoFactorAuthentication::class, \App\Actions\Fortify\EnableTwoFactorAuthentication::class);
        $this->app->bind(ConfirmTwoFactorAuthentication::class, \App\Actions\Fortify\ConfirmTwoFactorAuthentication::class);
        $this->app->bind(DisableTwoFactorAuthentication::class, \App\Actions\Fortify\DisableTwoFactorAuthentication::class);
        $this->app->bind(GenerateNewRecoveryCodes::class, \App\Actions\Fortify\GenerateNewRecoveryCodes::class);
        $this->app->bind(PrepareAuthenticatedSession::class, \App\Actions\Fortify\PrepareAuthenticatedSession::class);
        $this->app->bind(\Laravel\Fortify\Contracts\TwoFactorConfirmedResponse::class, TwoFactorConfirmedResponse::class);
        $this->app->bind(RedirectsIfTwoFactorAuthenticatable::class, RedirectIfTwoFactorAuthenticatable::class);
        $this->app->bind(\Laravel\Fortify\Http\Requests\TwoFactorLoginRequest::class, TwoFactorLoginRequest::class);
        $this->app->bind(\Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController::class, TwoFactorAuthenticatedSessionController::class);
        $this->app->bind(\Laravel\Fortify\Http\Controllers\ProfileInformationController::class, ProfileInformationController::class);
    }

    /**
     * Configure authentication screens, actions and rate limits.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User && request()->hasSession()) {
                request()->session()->put('auth_version', $event->user->auth_version);
            }
        });

        Fortify::loginView(fn (): View => view('auth.login'));
        Fortify::registerView(fn (): View => view('auth.register'));
        Fortify::verifyEmailView(fn (): View => view('auth.verify-email'));
        Fortify::confirmPasswordView(fn (): View => view('auth.password.confirm'));
        Fortify::requestPasswordResetLinkView(fn (): View => view('auth.password.forgot'));
        Fortify::resetPasswordView(fn (Request $request): View => view('auth.password.reset', ['request' => $request]));
        Fortify::twoFactorChallengeView(fn (Request $request): View => view('auth.two-factor.challenge', [
            'recovery' => $request->boolean('recovery'),
        ]));

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request): Limit {
            $username = $request->input(Fortify::username());

            return Limit::perMinute(5)->by(
                Str::transliterate(Str::lower(is_string($username) ? $username : '').'|'.$request->ip()),
            );
        });
        RateLimiter::for('two-factor', function (Request $request): Limit {
            $id = $request->session()->get('login.id');

            return Limit::perMinute(5)->by(is_int($id) || is_string($id) ? 'user:'.$id : 'ip:'.$request->ip());
        });
    }
}
