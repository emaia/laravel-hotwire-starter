<x-layouts.auth
    :title="__('Create your account')"
    :description="__('Start with your name, email and a secure password.')"
    :inline-errors="['name', 'email', 'password', 'password_confirmation']"
>
    <hw:form :action="route('register.store')" method="POST" frame="_top" error-focus error-scroll>
        <hw:field.group>
            <hw:field name="name" :label="__('Name')">
                <hw:input autocomplete="name" maxlength="255" required autofocus />
            </hw:field>
            <hw:field name="email" :label="__('Email')">
                <hw:input type="email" autocomplete="username" maxlength="255" required />
            </hw:field>
            <hw:field name="password" :label="__('Password')">
                <hw:input
                    type="password"
                    autocomplete="new-password"
                    :minlength="$passwordMinimumLength"
                    maxlength="1024"
                    :old="false"
                    required
                />
                <hw:field.description>
                    {{ __('Use at least :count characters with uppercase and lowercase letters, a number and a symbol.', ['count' => $passwordMinimumLength]) }}
                </hw:field.description>
            </hw:field>
            <hw:field name="password_confirmation" :label="__('Confirm password')">
                <hw:input type="password" autocomplete="new-password" :old="false" required />
            </hw:field>
            <x-auth.submit-button :label="__('Create account')" class="w-full" />
            <p class="text-muted-foreground text-center text-sm">
                {{ __('Already have an account?') }}
                <a href="{{ route('login') }}" class="text-foreground underline underline-offset-4">
                    {{ __('Log in') }}
                </a>
            </p>
        </hw:field.group>
    </hw:form>
</x-layouts.auth>
