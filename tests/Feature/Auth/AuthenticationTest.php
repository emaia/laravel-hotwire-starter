<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

test('visitors can register with only generic account fields', function (): void {
    $this->post(route('register.store'), [
        'name' => 'Starter User',
        'email' => 'starter@example.com',
        'password' => 'Starter-password-123',
        'password_confirmation' => 'Starter-password-123',
        'auth_version' => 99,
        'two_factor_secret' => 'untrusted',
    ])->assertRedirect('/dashboard');

    $user = User::query()->where('email', 'starter@example.com')->sole();
    $this->assertAuthenticatedAs($user);
    expect(Hash::check('Starter-password-123', $user->password))->toBeTrue();
    expect($user->auth_version)->toBe(0);
    expect($user->two_factor_secret)->toBeNull();
});

test('registration rejects invalid account data', function (array $input, string $field): void {
    $this->postJson(route('register.store'), array_replace([
        'name' => 'Starter User',
        'email' => 'starter@example.com',
        'password' => 'Starter-password-123',
        'password_confirmation' => 'Starter-password-123',
    ], $input))->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('users', 0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid email' => [['email' => 'invalid'], 'email'],
    'short password' => [['password' => '123', 'password_confirmation' => '123'], 'password'],
    'weak password' => [['password' => 'alllowercase', 'password_confirmation' => 'alllowercase'], 'password'],
    'unconfirmed password' => [['password_confirmation' => 'different'], 'password'],
]);

test('registration rejects an existing email without changing that account', function (): void {
    $user = User::factory()->create();

    $this->postJson(route('register.store'), [
        'name' => 'Another User', 'email' => $user->email,
        'password' => 'Starter-password-123', 'password_confirmation' => 'Starter-password-123',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->assertDatabaseCount('users', 1);
    expect($user->fresh()->name)->toBe($user->name);
});

test('malformed registration email is rejected before username canonicalization', function (): void {
    $this->postJson(route('register.store'), [
        'name' => 'New User', 'email' => ['invalid'],
        'password' => 'Starter-password-123', 'password_confirmation' => 'Starter-password-123',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->assertDatabaseCount('users', 0);
});

test('login authenticates a regular account without confirming password freshness', function (): void {
    $user = User::factory()->create();

    $this->withSession(['url.intended' => url('/account/password')])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(url('/account/password'));

    $this->assertAuthenticatedAs($user);
    expect(session('auth.password_confirmed_at'))->toBeNull();
});

test('invalid credentials do not authenticate an account', function (): void {
    $user = User::factory()->create();

    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'incorrect'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->assertGuest();
});

test('malformed login fields are rejected without server errors', function (): void {
    $this->postJson(route('login.store'), ['email' => ['invalid'], 'password' => 'password'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->assertGuest();
});

test('login attempts are rate limited', function (): void {
    $user = User::factory()->create();
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'incorrect'])
            ->assertUnprocessable();
    }

    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertTooManyRequests();

    $this->assertGuest();
});

test('logout ends the authenticated session', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

    $this->assertGuest();
});

test('a revoked session generation cannot access the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth_version' => $user->auth_version]);
    User::query()->whereKey($user->id)->increment('auth_version');

    $this->get(route('dashboard'))->assertRedirectToRoute('login');

    $this->assertGuest();
});
