<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(LazilyRefreshDatabase::class);

test('the starter defaults to Brazilian Portuguese with English fallback', function (): void {
    expect(config('app.locale'))->toBe('pt_BR');
    expect(config('app.fallback_locale'))->toBe('en');

    $this->get(route('login'))->assertOk()
        ->assertSee('lang="pt-BR"', false)
        ->assertSee('Entrar na sua conta')->assertSee('Esqueceu sua senha?')
        ->assertDontSee('Log in to your account');
});

test('authentication validation uses readable generic Portuguese field names', function (): void {
    app()->setLocale('pt_BR');

    $this->postJson(route('register.store'), [])->assertUnprocessable()->assertJsonValidationErrors([
        'name' => 'O campo nome é obrigatório.',
        'email' => 'O campo e-mail é obrigatório.',
        'password' => 'O campo senha é obrigatório.',
    ]);
});

test('login failures use the Portuguese authentication catalog', function (): void {
    app()->setLocale('pt_BR');
    $user = User::factory()->create();

    $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'incorrect'])
        ->assertUnprocessable()->assertJsonValidationErrors([
            'email' => 'Essas credenciais não correspondem aos nossos registros.',
        ]);
});

test('password reset notifications are localized without changing the reset URL', function (): void {
    app()->setLocale('pt_BR');
    $user = User::factory()->make();

    $mail = (new ResetPassword('test-token'))->toMail($user);

    expect($mail->subject)->toBe('Redefina sua senha');
    expect($mail->actionText)->toBe('Redefinir senha');
    expect($mail->actionUrl)->toContain('/reset-password/test-token');
    expect($mail->introLines)->toContain('Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha para sua conta.');
});

test('newer framework validation rules are available in Portuguese', function (): void {
    app()->setLocale('pt_BR');

    $validator = Validator::make(['options' => ['unexpected' => true]], ['options' => ['array_keys:allowed']]);

    expect($validator->errors()->first('options'))->toBe('O campo options deve conter apenas as seguintes chaves: allowed.');
});

test('English remains available for the same screens and validation messages', function (): void {
    app()->setLocale('en');

    $this->get(route('login'))->assertOk()->assertSee('Log in to your account')->assertDontSee('Entrar na sua conta');
    expect(Validator::make([], ['email' => ['required']])->errors()->first('email'))->toBe('The email field is required.');
});
