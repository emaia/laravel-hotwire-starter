<x-layouts.settings
    :title="__('Change your password')"
    :description="__('Your other sessions will be signed out after this change.')"
    :inline-errors="['current_password', 'password', 'password_confirmation']"
>
    <hw:form :action="route('user-password.update')" method="PUT" frame="_top" error-focus error-scroll>
        <hw:field.group>
            <hw:field name="current_password" :label="__('Current password')">
                <hw:input type="password" autocomplete="current-password" :old="false" required autofocus />
            </hw:field>
            <hw:field name="password" :label="__('New password')">
                <hw:input
                    type="password"
                    autocomplete="new-password"
                    :minlength="$passwordMinimumLength"
                    maxlength="1024"
                    :old="false"
                    required
                />
            </hw:field>
            <hw:field name="password_confirmation" :label="__('Confirm password')">
                <hw:input type="password" autocomplete="new-password" :old="false" required />
            </hw:field>
            <x-auth.submit-button :label="__('Update password')" class="w-full" />
        </hw:field.group>
    </hw:form>
</x-layouts.settings>
