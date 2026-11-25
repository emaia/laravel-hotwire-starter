<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentUserAccess
{
    /**
     * Reject revoked sessions even if an in-flight request recreated their storage.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof User) {
            $current = User::query()->select(['id', 'auth_version'])->find($user->id);
            if ($current === null || $current->auth_version !== $user->auth_version
                || (int) $request->session()->get('auth_version', 0) !== $current->auth_version) {
                Auth::logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $request->expectsJson()
                    ? response()->json(['message' => __('Unauthenticated.')], 401)
                    : redirect()->route('login');
            }
        }

        return $next($request);
    }
}
