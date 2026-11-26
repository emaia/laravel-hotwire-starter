<?php

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(LazilyRefreshDatabase::class);

test('malformed reset emails are rejected without server errors', function (): void {
    $this->postJson(route('password.email'), ['email' => ['invalid']])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('a password reset link is sent to the account email', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('resetting a password preserves 2fa and invalidates old access', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $token = Password::broker()->createToken($user);
    $secret = $user->two_factor_secret;

    $this->post(route('password.update'), [
        'token' => $token, 'email' => $user->email,
        'password' => 'Changed-password-123', 'password_confirmation' => 'Changed-password-123',
    ])->assertSessionHasNoErrors()->assertRedirectToRoute('login');

    $user->refresh();
    expect(Hash::check('Changed-password-123', $user->password))->toBeTrue();
    expect($user->two_factor_secret)->toBe($secret);
    expect($user->auth_version)->toBe(1);
    $this->assertGuest();
});

test('an invalid reset token leaves credentials unchanged', function (): void {
    $user = User::factory()->create();

    $this->postJson(route('password.update'), [
        'token' => 'invalid', 'email' => $user->email,
        'password' => 'Changed-password-123', 'password_confirmation' => 'Changed-password-123',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    expect($user->fresh()->password)->toBe($user->password);
});

test('changing a password requires the current password and retains the initiating session', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'Changed-password-123', 'password_confirmation' => 'Changed-password-123',
    ])->assertSuccessful();

    expect(Hash::check('Changed-password-123', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->auth_version)->toBe(1);
    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticatedAs($user);
});

test('an incorrect current password cannot change credentials', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson(route('user-password.update'), [
        'current_password' => 'incorrect',
        'password' => 'Changed-password-123', 'password_confirmation' => 'Changed-password-123',
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

    expect($user->fresh()->password)->toBe($user->password);
    expect($user->fresh()->auth_version)->toBe(0);
});

test('changing a password validates the current password on the Fortify guard', function (): void {
    config([
        'auth.guards.account' => ['driver' => 'session', 'provider' => 'users'],
        'fortify.guard' => 'account',
    ]);
    $user = User::factory()->create();
    $this->actingAs($user, 'account');

    app(UpdateUserPassword::class)->update($user, [
        'current_password' => 'password',
        'password' => 'Changed-password-123',
        'password_confirmation' => 'Changed-password-123',
    ]);

    expect(Hash::check('Changed-password-123', $user->fresh()->password))->toBeTrue();
});

test('confirming the current password unlocks security settings', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('two-factor.settings'))->assertRedirectToRoute('password.confirm');

    $this->post(route('password.confirm.store'), ['password' => 'password'])->assertRedirect();

    $this->get(route('two-factor.settings'))->assertOk();
    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});
