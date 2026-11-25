<x-layouts.auth
    :title="__('Forgot your password?')"
    :description="__('Enter your account email to receive a password reset link.')"
    :inline-errors="['email']"
>
    <hw:form :action="route('password.email')" method="POST" frame="_top" error-focus error-scroll>
        <hw:field.group>
            <hw:field name="email" :label="__('Email')">
                <hw:input type="email" autocomplete="email" required autofocus />
            </hw:field>
            <x-auth.submit-button :label="__('Send reset link')" class="w-full" />
        </hw:field.group>
    </hw:form>
    <x-slot:footer>
        <a href="{{ route('login') }}" class="underline underline-offset-4">{{ __('Back to login') }}</a>
    </x-slot:footer>
</x-layouts.auth>
