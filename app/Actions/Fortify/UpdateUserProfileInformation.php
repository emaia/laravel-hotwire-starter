<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(private readonly AccountCredentials $credentials) {}

    /**
     * Update the current account and revoke older access when its login email changes.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        if (config('fortify.lowercase_usernames') && is_string($input['email'] ?? null)) {
            $input['email'] = Str::lower(trim($input['email']));
        }
        $originalEmail = $user->email;
        try {
            $this->credentials->change($user, function (User $current) use ($input): void {
                $emailChanged = is_string($input['email'] ?? null) && $input['email'] !== $current->email;
                $validated = Validator::make($input, [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($current->id)],
                    'current_password' => $emailChanged
                        ? ['required', 'string', 'max:1024', 'current_password:'.config('fortify.guard', 'web')]
                        : ['nullable', 'string', 'max:1024'],
                ])->validate();
                $current->forceFill(['name' => $validated['name'], 'email' => $validated['email']]);
                if ($emailChanged) {
                    $current->email_verified_at = null;
                }
                $current->save();
                if ($emailChanged) {
                    $this->credentials->revokeOtherAccess($current);
                }
            }, 'email');
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'email' => __('validation.unique', ['attribute' => __('validation.attributes.email')]),
            ]);
        }

        if ($user->email !== $originalEmail) {
            $user->sendEmailVerificationNotification();
        }
    }
}
