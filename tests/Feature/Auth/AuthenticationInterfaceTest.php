<?php

use App\Actions\Fortify\DisableTwoFactorAuthentication;
use App\Models\User;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

uses(LazilyRefreshDatabase::class);

/**
 * Reload guards and sessions between requests as independent browser requests do.
 *
 * @param  array<string, mixed>  $data
 */
function requestWithPersistedAuthSession(string $method, string $url, array $data = []): TestResponse
{
    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');
    foreach (app('router')->getRoutes() as $route) {
        $route->flushController();
    }
    test()->withCredentials();
    $response = test()->{$method}($url, $data);
    if ($cookie = $response->getCookie(config('session.cookie'))) {
        test()->withCookie(config('session.cookie'), $cookie->getValue());
    }

    return $response;
}

test('public authentication forms include a csrf token', function (string $route): void {
    $this->get(route($route))->assertOk()->assertSee('name="_token"', false);
})->with(['login', 'register', 'password.request']);

test('invalid credentials appear once and are associated with the email field', function (): void {
    $response = $this->followingRedirects()->from(route('login'))->post(route('login.store'), [
        'email' => 'unknown@example.test',
        'password' => 'incorrect',
    ])->assertOk();

    $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
    $email = $document->querySelector('input[name="email"]');
    $message = __('auth.failed');

    expect($email?->getAttribute('aria-invalid'))->toBe('true');
    expect($document->getElementById($email->getAttribute('aria-describedby'))?->textContent)->toContain($message);
    expect(substr_count($document->body->textContent, $message))->toBe(1);
});

test('password reset shows an error for a missing hidden token without repeating the email error', function (): void {
    app()->setLocale('en');
    $reset = route('password.reset', ['token' => 'reset-token']);

    $response = $this->followingRedirects()->from($reset)->post(route('password.update'), [
        'password' => 'Password-123!',
    ])->assertOk();

    $document = HTMLDocument::createFromString($response->getContent(), LIBXML_NOERROR);
    $alert = $document->querySelector('[role="alert"]:not([id])');

    expect($alert?->textContent)->toContain('The token field is required.')->not->toContain('The email field is required.');
    expect(substr_count($document->body->textContent, 'The email field is required.'))->toBe(1);
});

test('account screens require authentication', function (string $route): void {
    $this->get(route($route))->assertRedirectToRoute('login');
})->with(['dashboard', 'account.password.edit', 'two-factor.settings', 'two-factor.recovery']);

test('secret material is visible only during pending setup', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $this->postJson(route('two-factor.enable'));

    $this->get(route('two-factor.settings'))->assertOk()
        ->assertSee('data-two-factor-secret', false)->assertSee('<svg', false)
        ->assertHeader('Cache-Control', 'no-store, private');
});

test('confirmed accounts cannot retrieve setup secrets or qr codes', function (string $route): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route($route))->assertNotFound()
        ->assertHeader('Cache-Control', 'no-store, private');
})->with(['two-factor.qr-code', 'two-factor.secret-key']);

test('recovery codes are available only after confirmation and are never cached', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.recovery'))->assertOk()
        ->assertSee('test-recovery-one')->assertHeader('Cache-Control', 'no-store, private');
});

test('an absent challenge returns to login instead of rendering a broken form', function (): void {
    $this->get(route('two-factor.login'))->assertRedirectToRoute('login');
});

test('activation retains the initiating database session while deleting other sessions', function (): void {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    requestWithPersistedAuthSession('post', route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
    requestWithPersistedAuthSession('post', route('password.confirm.store'), ['password' => 'password'])->assertRedirect();
    requestWithPersistedAuthSession('post', route('two-factor.enable'))->assertRedirect();
    $code = app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret));
    DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    $oldSessionId = session()->getId();

    requestWithPersistedAuthSession('post', route('two-factor.confirm'), ['code' => $code])->assertRedirectToRoute('two-factor.recovery');

    $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
    $this->assertDatabaseMissing('sessions', ['id' => $oldSessionId]);
    requestWithPersistedAuthSession('get', route('two-factor.recovery'))->assertOk();
    $this->assertAuthenticatedAs($user);
});

test('a restored remember cookie does not count as recent password confirmation', function (): void {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $guard = Auth::guard();
    $this->withCookie($guard->getRecallerName(), implode('|', [
        $user->id, $user->getRememberToken(), $guard->hashPasswordForCookie($user->password),
    ]));

    requestWithPersistedAuthSession('get', route('two-factor.settings'))->assertRedirectToRoute('password.confirm');

    $this->assertAuthenticatedAs($user);
    expect(session('auth.password_confirmed_at'))->toBeNull();
});

test('stale credential mutations cannot overwrite a newer generation', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    User::query()->whereKey($user->id)->increment('auth_version');

    expect(fn () => app(DisableTwoFactorAuthentication::class)($user))->toThrow(ValidationException::class);

    expect($user->fresh()->two_factor_secret)->toBe($user->two_factor_secret);
});
