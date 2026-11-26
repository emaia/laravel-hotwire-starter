<?php

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;

uses(LazilyRefreshDatabase::class);

test('guests cannot edit their profile', function (): void {
    $this->get(route('account.profile.edit'))->assertRedirectToRoute('login');
});

test('the profile remains accessible before email verification', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('account.profile.edit'))->assertOk()
        ->assertSee(route('verification.send'), false);
});

test('an unverified account can open its profile without a resend form when email verification is disabled', function (): void {
    config(['fortify.features' => array_diff(config('fortify.features'), [Features::emailVerification()])]);
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('account.profile.edit'))->assertOk()
        ->assertDontSee(route('verification.send'), false);
});

test('account security pages disable turbo prefetch', function (string $route): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->get(route($route))->assertOk()
        ->assertSee('name="turbo-prefetch" content="false"', false);
})->with(['account.password.edit', 'two-factor.settings', 'two-factor.recovery']);

test('changing only the name preserves verification sessions and two factor credentials', function (): void {
    Notification::fake();
    $user = User::factory()->withTwoFactorAuthentication()->create();

    $this->actingAs($user)->putJson(route('user-profile-information.update'), [
        'name' => 'Novo nome', 'email' => $user->email,
    ])->assertOk();

    $updated = $user->fresh();
    expect($updated->name)->toBe('Novo nome');
    expect($updated->email_verified_at->equalTo($user->email_verified_at))->toBeTrue();
    expect($updated->auth_version)->toBe(0);
    expect($updated->two_factor_secret)->toBe($user->two_factor_secret);
    Notification::assertNothingSent();
});

test('changing email requires the current password and revokes older access without removing 2fa', function (): void {
    Notification::fake();
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $token = $user->remember_token;

    $this->actingAs($user)->putJson(route('user-profile-information.update'), [
        'name' => $user->name, 'email' => 'NEW@example.test', 'current_password' => 'password',
    ])->assertOk();

    $updated = $user->fresh();
    expect($updated->email)->toBe('new@example.test');
    expect($updated->email_verified_at)->toBeNull();
    expect($updated->auth_version)->toBe(1);
    expect($updated->remember_token)->not->toBe($token);
    expect($updated->two_factor_secret)->toBe($user->two_factor_secret);
    $this->get(route('account.profile.edit'))->assertOk();
    $this->get(route('dashboard'))->assertRedirectToRoute('verification.notice');
    Notification::assertSentTo($updated, VerifyEmail::class);
});

test('normalizing the same email does not invalidate verification', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'same@example.test']);

    $this->actingAs($user)->putJson(route('user-profile-information.update'), [
        'name' => $user->name, 'email' => 'SAME@example.test',
    ])->assertOk();

    expect($user->fresh()->email)->toBe('same@example.test');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect($user->fresh()->auth_version)->toBe(0);
    Notification::assertNothingSent();
});

test('an email change without a valid current password leaves the account untouched', function (?string $password): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->putJson(route('user-profile-information.update'), [
        'name' => 'Changed', 'email' => 'changed@example.test', 'current_password' => $password,
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

    expect($user->fresh()->email)->toBe($user->email);
    expect($user->fresh()->name)->toBe($user->name);
    expect($user->fresh()->auth_version)->toBe(0);
    Notification::assertNothingSent();
})->with([null, 'incorrect']);

test('profile updates reject malformed fields and existing emails', function (array $input, string $field): void {
    $user = User::factory()->create();
    $owner = User::factory()->create(['email' => 'owner@example.test']);

    $this->actingAs($user)->putJson(route('user-profile-information.update'), array_replace([
        'name' => $user->name, 'email' => $user->email,
    ], $input))->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($user->fresh()->email)->toBe($user->email);
    expect($owner->fresh()->email)->toBe('owner@example.test');
})->with([
    'malformed email' => [['email' => ['invalid']], 'email'],
    'empty name' => [['name' => ''], 'name'],
    'email owned by someone else' => [['email' => 'OWNER@example.test', 'current_password' => 'password'], 'email'],
]);

test('profile fields cannot select another account or modify credential attributes', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->putJson(route('user-profile-information.update'), [
        'id' => $other->id, 'name' => 'My name', 'email' => $user->email,
        'password' => 'Attacker-password-123', 'auth_version' => 99, 'email_verified_at' => null,
    ])->assertOk();

    expect($user->fresh()->name)->toBe('My name');
    expect($user->fresh()->password)->toBe($user->password);
    expect($user->fresh()->auth_version)->toBe(0);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect($other->fresh()->name)->toBe($other->name);
});

test('stale profile changes cannot overwrite newer credentials', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    User::query()->whereKey($user->id)->increment('auth_version');

    expect(fn () => app(UpdateUserProfileInformation::class)->update($user, [
        'name' => 'Stale', 'email' => $user->email,
    ]))->toThrow(ValidationException::class);

    expect($user->fresh()->name)->toBe($user->name);
    Notification::assertNothingSent();
});

test('a database email conflict is returned as a validation error and rolls back the change', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $event = 'eloquent.saving: '.User::class;
    Event::listen($event, function (User $saving) use ($user, $other): void {
        if ($saving->is($user) && $saving->isDirty('email')) {
            $other->forceFill(['email' => $saving->email])->saveQuietly();
        }
    });

    try {
        $this->actingAs($user)->putJson(route('user-profile-information.update'), [
            'name' => $user->name, 'email' => 'conflict@example.test', 'current_password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    } finally {
        Event::forget($event);
    }

    expect($user->fresh()->email)->toBe($user->email);
    expect($user->fresh()->auth_version)->toBe(0);
    Notification::assertNothingSent();
});
