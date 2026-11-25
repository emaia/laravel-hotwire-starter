<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountCredentials
{
    public function __construct(protected readonly Request $request) {}

    /**
     * Serialize credential changes and preserve only the initiating session's generation.
     *
     * @param  Closure(User): void  $change
     *
     * @throws ValidationException
     * @throws UniqueConstraintViolationException
     */
    public function change(User $user, Closure $change, string $errorKey = 'code'): void
    {
        $current = DB::transaction(function () use ($user, $change, $errorKey): User {
            $current = User::query()->lockForUpdate()->find($user->id);
            if ($current === null || $current->auth_version !== $user->auth_version
                || $current->password !== $user->password || $current->two_factor_secret !== $user->two_factor_secret) {
                throw ValidationException::withMessages([$errorKey => __('Your account credentials changed. Please sign in again.')]);
            }

            $change($current);

            return $current;
        });

        $versionChanged = $current->auth_version !== $user->auth_version;
        $user->setRawAttributes($current->getAttributes(), true);
        if ($this->request->hasSession() && $this->request->user()?->is($current)) {
            $this->request->session()->put('auth_version', $current->auth_version);
            if ($versionChanged) {
                // Reset the database handler after its persisted session was revoked.
                $this->request->session()->regenerate(true);
            }
        }
    }

    /** Invalidate existing sessions and remember cookies across session drivers. */
    public function revokeOtherAccess(User $user): void
    {
        $user->forceFill([
            'auth_version' => $user->auth_version + 1,
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->delete();
        }
    }
}
