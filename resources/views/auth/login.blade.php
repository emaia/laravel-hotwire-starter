<x-layouts.auth
    :title="__('Log in to your account')"
    :description="__('Enter your email and password to continue.')"
    :inline-errors="['email', 'password']"
>
    <hw:form :action="route('login.store')" method="POST" frame="_top" error-focus error-scroll>
        <hw:field.group>
            <hw:field name="email" :label="__('Email')">
                <hw:input type="email" autocomplete="username" required autofocus />
            </hw:field>
            <hw:field name="password">
                <div class="flex items-center justify-between gap-4">
                    <hw:field.label>{{ __('Password') }}</hw:field.label>
                    <a href="{{ route('password.request') }}" class="text-sm underline-offset-4 hover:underline">
                        {{ __('Forgot your password?') }}
                    </a>
                </div>
                <hw:input type="password" autocomplete="current-password" :old="false" required />
            </hw:field>
            <label class="flex items-center gap-2 text-sm">
                <hw:checkbox name="remember" value="1" />
                {{ __('Remember me') }}
            </label>
            <x-auth.submit-button :label="__('Log in')" class="w-full" />
            @if (Route::has('register'))
                <p class="text-muted-foreground text-center text-sm">
                    {{ __("Don't have an account?") }}
                    <a href="{{ route('register') }}" class="text-foreground underline underline-offset-4">
                        {{ __('Sign up') }}
                    </a>
                </p>
            @endif
        </hw:field.group>
    </hw:form>
</x-layouts.auth>
