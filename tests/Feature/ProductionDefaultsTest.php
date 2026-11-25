<?php

use App\Models\User;
use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

uses(LazilyRefreshDatabase::class);

test('application dates are immutable', function (): void {
    $date = Date::now();
    $tomorrow = $date->addDay();

    expect($date)->toBeInstanceOf(CarbonImmutable::class);
    expect($tomorrow->isAfter($date))->toBeTrue();
});

test('local passwords retain the existing composition requirements', function (): void {
    Http::preventStrayRequests();

    expect(Validator::make(['password' => 'Aa12!bbb'], ['password' => [Password::default()]])->passes())->toBeTrue();
    expect(Validator::make(['password' => 'alllowercase'], ['password' => [Password::default()]])->fails())->toBeTrue();
    Http::assertNothingSent();
});

test('production passwords require at least twelve characters', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');
    (new AppServiceProvider($this->app))->boot();
    Http::preventStrayRequests();

    expect(Validator::make(['password' => 'Abcdef12!xy'], ['password' => [Password::default()]])->fails())->toBeTrue();
    expect(Validator::make(['password' => 'Abcdef12!xyz'], ['password' => [Password::default()]])->passes())->toBeTrue();
    Http::assertNothingSent();
});

test('registration and its form consume the production password policy', function (): void {
    User::factory()->create();
    $this->app->detectEnvironment(fn (): string => 'production');
    (new AppServiceProvider($this->app))->boot();

    $this->get(route('register'))->assertOk()->assertSee('minlength="12"', false);
    $this->withSession(['_token' => 'policy-test-token'])->postJson(route('register.store'), [
        '_token' => 'policy-test-token',
        'name' => 'New User', 'email' => 'new@example.test',
        'password' => 'Abcdef12!xy', 'password_confirmation' => 'Abcdef12!xy',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');

    $this->assertDatabaseCount('users', 1);
});

test('compromised password checks are explicitly opt in and isolated from the network', function (): void {
    config(['auth.password_policy.uncompromised' => true]);
    $password = 'Abcdef12!xyz';
    $suffix = substr(strtoupper(sha1($password)), 5);
    Http::preventStrayRequests();
    Http::fake(['https://api.pwnedpasswords.com/range/*' => Http::response($suffix.':100')]);

    expect(Validator::make(['password' => $password], ['password' => [Password::default()]])->fails())->toBeTrue();
    Http::assertSentCount(1);
});

test('force does not bypass destructive command protection in production', function (string $command): void {
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
    $this->app->detectEnvironment(fn (): string => 'production');
    (new AppServiceProvider($this->app))->boot();

    $this->artisan($command, ['--force' => true])->assertFailed();
})->with(['db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback']);
