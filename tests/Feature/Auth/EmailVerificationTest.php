<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(LazilyRefreshDatabase::class);

test('verification mail is localized and keeps the signed verification URL', function (): void {
    $user = User::factory()->unverified()->create();

    $mail = (new VerifyEmail)->toMail($user);

    expect($mail->subject)->toBe('Verifique seu endereço de e-mail');
    expect($mail->actionText)->toBe('Verificar endereço de e-mail');
    expect($mail->actionUrl)->toContain('/email/verify/'.$user->id.'/'.sha1($user->email))->toContain('signature=');
});

test('registration sends verification without marking the new email as verified', function (): void {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'New User', 'email' => 'new@example.test',
        'password' => 'Starter-password-123', 'password_confirmation' => 'Starter-password-123',
    ])->assertRedirect('/dashboard');

    $user = User::query()->where('email', 'new@example.test')->sole();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->get(route('dashboard'))->assertRedirectToRoute('verification.notice');
});

test('unverified users see a localized notice with profile correction and logout', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))->assertOk()
        ->assertSee('Verifique seu e-mail')
        ->assertSee(route('account.profile.edit'), false)
        ->assertSee(route('logout'), false)
        ->assertHeader('Cache-Control', 'no-store, private');
});

test('a valid signed link verifies the current user and dispatches the event', function (): void {
    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
    $this->get(route('dashboard'))->assertOk();
});

test('a signed link for another email cannot verify the account', function (): void {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
        'id' => $user->id, 'hash' => sha1('another@example.test'),
    ]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('a verification link issued before an email change cannot verify the new address', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);
    $this->actingAs($user)->putJson(route('user-profile-information.update'), [
        'name' => $user->name, 'email' => 'changed@example.test', 'current_password' => 'password',
    ])->assertOk();

    $this->get($url)->assertForbidden();

    expect($user->fresh()->email)->toBe('changed@example.test');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('expired signed verification links are rejected', function (): void {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinute(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);
    $this->travel(2)->minutes();

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('a user cannot verify a different account using its signed link', function (): void {
    $user = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
        'id' => $other->id, 'hash' => sha1($other->email),
    ]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($other->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('resending verification is rate limited', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->postJson(route('verification.send'))->assertAccepted();
    }

    $this->postJson(route('verification.send'))->assertTooManyRequests();

    Notification::assertSentToTimes($user, VerifyEmail::class, 6);
});

test('resending verification shows polite success feedback for browser requests', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->from(route('verification.notice'))
        ->post(route('verification.send'))->assertRedirectToRoute('verification.notice');

    $this->get(route('verification.notice'))->assertOk()
        ->assertSee('role="status"', false)
        ->assertSee('Um novo link de verificação foi enviado ao seu e-mail.');
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('guests cannot access the verification notice or send verification mail', function (): void {
    Notification::fake();

    $this->get(route('verification.notice'))->assertRedirectToRoute('login');
    $this->post(route('verification.send'))->assertRedirectToRoute('login');

    Notification::assertNothingSent();
});
