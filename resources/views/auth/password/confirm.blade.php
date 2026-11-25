<x-layouts.auth
    :title="__('Confirm your password')"
    :description="__('For your security, enter your password before continuing.')"
    :inline-errors="['password']"
>
    <hw:form :action="route('password.confirm.store')" method="POST" frame="_top" error-focus error-scroll>
        <hw:field.group>
            <hw:field name="password" :label="__('Password')">
                <hw:input type="password" autocomplete="current-password" :old="false" required autofocus />
            </hw:field>
            <x-auth.submit-button :label="__('Confirm password')" class="w-full" />
        </hw:field.group>
    </hw:form>
</x-layouts.auth>
