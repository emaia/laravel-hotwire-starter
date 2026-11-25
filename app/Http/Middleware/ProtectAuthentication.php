<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class ProtectAuthentication
{
    /**
     * Limit sensitive account operations and suppress caching of authentication responses.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof User && $user->two_factor_confirmed_at !== null
            && $request->routeIs('two-factor.qr-code', 'two-factor.secret-key')) {
            abort(404);
        }
        if ($user instanceof User && $user->two_factor_confirmed_at === null && $request->routeIs('two-factor.recovery-codes')) {
            abort(404);
        }

        $email = $request->input('email');
        $emailKey = is_string($email) ? Str::lower($email) : '';
        $limit = match (true) {
            $request->routeIs('register.store') => ['registration:'.$request->ip(), 5, 3600],
            $request->routeIs('password.email') => ['password-reset:'.$emailKey.'|'.$request->ip(), 2, 3600],
            $request->routeIs('password.update') => ['password-reset-submit:'.$request->ip(), 5, 60],
            $user instanceof User && $request->routeIs('password.confirm.store', 'user-password.update', 'user-profile-information.update') => ['account-password:'.$user->id, 5, 60],
            $user instanceof User && $request->routeIs('two-factor.enable', 'two-factor.confirm', 'two-factor.disable', 'two-factor.regenerate-recovery-codes') => ['two-factor-management:'.$user->id, 5, 60],
            default => null,
        };
        if ($limit !== null) {
            [$key, $attempts, $seconds] = $limit;
            if (RateLimiter::tooManyAttempts($key, $attempts)) {
                abort(429, __('Too many attempts. Please try again later.'), ['Retry-After' => (string) RateLimiter::availableIn($key)]);
            }
            RateLimiter::hit($key, $seconds);
        }
        if ($request->routeIs('register.store') && $request->exists(Fortify::username())
            && ! is_string($request->input(Fortify::username()))) {
            $request->validate([Fortify::username() => ['required', 'string']]);
        }

        $response = $next($request);
        if (self::isSensitive($request)) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    /** Identify account responses whose HTML or JSON must not persist in caches. */
    public static function isSensitive(Request $request): bool
    {
        return $request->routeIs('login*', 'logout', 'register*', 'password.*', 'user-password.*', 'user-profile-information.*', 'account.*', 'two-factor.*', 'verification.*');
    }
}
