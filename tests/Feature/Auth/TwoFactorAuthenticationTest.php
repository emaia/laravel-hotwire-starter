<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

uses(LazilyRefreshDatabase::class);

test('malformed challenge account identifiers are rejected without server errors', function (mixed $id): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->withSession(['login.id' => $id]);

    $this->postJson(route('two-factor.login.store'), ['recovery_code' => 'test-recovery-one'])
        ->assertUnprocessable()->assertJsonValidationErrors('code')->assertSessionMissing('login');

    $this->assertGuest();
})->with([
    'array' => [['invalid']],
    'boolean' => [true],
    'non numeric' => ['invalid'],
    'zero' => [0],
]);

test('2fa keeps setup pending until a valid authenticator code confirms it', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $this->postJson(route('two-factor.enable'))->assertSuccessful();
    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->toArray())->not->toHaveKeys(['two_factor_secret', 'two_factor_recovery_codes']);
    $this->postJson(route('two-factor.confirm'), ['code' => 'invalid'])
        ->assertUnprocessable()->assertJsonValidationErrors('code');
    $code = app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));

    $this->postJson(route('two-factor.confirm'), ['code' => $code])->assertSuccessful();

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
    expect($user->fresh()->auth_version)->toBe(1);
    $this->get(route('dashboard'))->assertOk();
});

test('2fa management requires recent password confirmation', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('two-factor.enable'))->assertStatus(423);

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('pending setup can be cancelled without revoking sessions', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $this->postJson(route('two-factor.enable'))->assertSuccessful();

    $this->deleteJson(route('two-factor.disable'))->assertSuccessful();

    expect($user->fresh()->two_factor_secret)->toBeNull();
    expect($user->fresh()->auth_version)->toBe(0);
});

test('a confirmed authenticator cannot be overwritten by enabling again', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->postJson(route('two-factor.enable'), ['force' => true])->assertUnprocessable();

    expect($user->fresh()->two_factor_secret)->toBe($user->two_factor_secret);
});

test('disabling 2fa requires possession and revokes the authenticator', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $this->deleteJson(route('two-factor.disable'))->assertUnprocessable();

    $this->deleteJson(route('two-factor.disable'), ['recovery_code' => 'test-recovery-one'])->assertSuccessful();

    expect($user->fresh()->two_factor_secret)->toBeNull();
    expect($user->fresh()->two_factor_recovery_codes)->toBeNull();
    expect($user->fresh()->auth_version)->toBe(1);
});

test('regenerating recovery codes requires possession and invalidates the old set', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $this->postJson(route('two-factor.regenerate-recovery-codes'))->assertUnprocessable();

    $this->postJson(route('two-factor.regenerate-recovery-codes'), ['recovery_code' => 'test-recovery-one'])
        ->assertSuccessful();

    expect($user->fresh()->recoveryCodes())->toHaveCount(8)->not->toContain('test-recovery-one', 'test-recovery-two');
    expect($user->fresh()->auth_version)->toBe(1);
});

test('password authentication remains pending until the second factor is valid', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $this->withSession(['url.intended' => url('/account/password')])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirectToRoute('two-factor.login');
    $this->assertGuest();

    $this->post(route('two-factor.login.store'), ['recovery_code' => 'test-recovery-one'])
        ->assertRedirect(url('/account/password'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->recoveryCodes())->not->toContain('test-recovery-one');
    expect(session()->has('login.id'))->toBeFalse();
    expect(session('auth.password_confirmed_at'))->toBeNull();
});

test('an authenticator code completes the password challenge', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $code = app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->two_factor_secret));
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['code' => $code])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->recoveryCodes())->toContain('test-recovery-one', 'test-recovery-two');
});

test('a recovery code cannot be reused in another password challenge', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $credentials = ['email' => $user->email, 'password' => 'password'];
    $this->post(route('login.store'), $credentials);
    $this->postJson(route('two-factor.login.store'), ['recovery_code' => 'test-recovery-one'])->assertSuccessful();
    $this->post(route('logout'));
    $this->post(route('login.store'), $credentials);

    $this->postJson(route('two-factor.login.store'), ['recovery_code' => 'test-recovery-one'])->assertUnprocessable();

    $this->assertGuest();
});

test('expired or superseded challenges do not consume recovery codes', function (string $cause): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    if ($cause === 'expiry') {
        $this->travel(5)->minutes();
    } else {
        User::query()->whereKey($user->id)->increment('auth_version');
    }

    $this->postJson(route('two-factor.login.store'), ['recovery_code' => 'test-recovery-one'])->assertUnprocessable();

    $this->assertGuest();
    expect($user->fresh()->recoveryCodes())->toContain('test-recovery-one');
    expect(session()->has('login.id'))->toBeFalse();
})->with(['expiry', 'credentials changed']);

test('failed challenge attempts are rate limited', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route('two-factor.login.store'), ['code' => 'invalid'])->assertUnprocessable();
    }

    $this->postJson(route('two-factor.login.store'), ['recovery_code' => 'test-recovery-one'])->assertTooManyRequests();

    $this->assertGuest();
});

test('factor management attempts are rate limited', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->deleteJson(route('two-factor.disable'), ['code' => 'invalid'])->assertUnprocessable();
    }

    $this->deleteJson(route('two-factor.disable'), ['recovery_code' => 'test-recovery-one'])->assertTooManyRequests();

    expect($user->fresh()->two_factor_secret)->toBe($user->two_factor_secret);
});

test('pending setup does not expose recovery codes', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $this->postJson(route('two-factor.enable'))->assertSuccessful();

    $this->getJson(route('two-factor.recovery-codes'))->assertNotFound();
    $this->get(route('two-factor.recovery'))->assertNotFound();
});

test('failed proofs are never flashed back into the session', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->from(route('two-factor.login'))
        ->post(route('two-factor.login.store'), ['recovery_code' => 'private-invalid-code'])
        ->assertSessionHasErrors('code')->assertSessionMissing('_old_input.recovery_code');
});
