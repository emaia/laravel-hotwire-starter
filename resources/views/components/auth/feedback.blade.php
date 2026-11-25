@props(['inlineErrors' => []])

@php
    $formErrors = collect($errors->getMessages())->except($inlineErrors)->flatten()->unique();
@endphp

@if (session('status'))
    {{-- Keep success feedback polite; the alert component fixes its root role to alert. --}}
    <div data-slot="alert" data-variant="default" role="status" data-turbo-temporary>
        <hw:alert.title>
            {{
                match (session('status')) {
                    'two-factor-authentication-enabled' => __('Confirm a code from your app to finish setup.'),
                    'two-factor-authentication-confirmed' => __('Two-factor authentication is now enabled.'),
                    'two-factor-authentication-disabled' => __('Two-factor authentication has been disabled.'),
                    'recovery-codes-generated' => __('New recovery codes have been generated.'),
                    'password-updated' => __('Your password has been updated.'),
                    'profile-information-updated' => __('Your profile has been updated.'),
                    'verification-link-sent' => __('A new verification link has been sent to your email.'),
                    default => session('status'),
                }
            }}
        </hw:alert.title>
    </div>
@endif
@if ($formErrors->isNotEmpty())
    <div class="text-destructive flex flex-col gap-1 text-sm" role="alert" data-turbo-temporary>
        @foreach ($formErrors as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
