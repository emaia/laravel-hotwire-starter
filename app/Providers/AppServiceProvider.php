<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Apply production safeguards and the shared password policy.
     */
    public function boot(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
        Date::use(CarbonImmutable::class);
        Password::defaults(function (): Password {
            $rule = Password::min($this->passwordMinimumLength())->letters()->mixedCase()->numbers()->symbols();

            return config('auth.password_policy.uncompromised') ? $rule->uncompromised() : $rule;
        });
        ViewFacade::composer(['auth.register', 'auth.password.reset', 'auth.password.edit'], function (View $view): void {
            $view->with('passwordMinimumLength', $this->passwordMinimumLength());
        });
    }

    private function passwordMinimumLength(): int
    {
        return $this->app->isProduction() ? 12 : 8;
    }
}
