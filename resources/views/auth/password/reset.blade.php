<x-layouts.auth
    :title="__('Reset your password')"
    :description="__('Choose a new password for your account.')"
    :inline-errors="['email', 'password', 'password_confirmation']"
>
    <hw:form :action="route('password.update')" method="POST" frame="_top" error-focus error-scroll>
        <input type="hidden" name="token" value="{{ $request->route('token') }}" />
        <hw:field.group>
            <hw:field name="email" :label="__('Email')">
                <hw:input type="email" :value="$request->email" autocomplete="username" required />
            </hw:field>
            <hw:field name="password" :label="__('New password')">
                <hw:input
                    type="password"
                    autocomplete="new-password"
                    :minlength="$passwordMinimumLength"
                    maxlength="1024"
                    :old="false"
                    required
                    autofocus
                />
            </hw:field>
            <hw:field name="password_confirmation" :label="__('Confirm password')">
                <hw:input type="password" autocomplete="new-password" :old="false" required />
            </hw:field>
            <x-auth.submit-button :label="__('Reset password')" class="w-full" />
        </hw:field.group>
    </hw:form>
</x-layouts.auth>
