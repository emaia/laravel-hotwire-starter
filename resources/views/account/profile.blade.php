<x-layouts.settings
    :title="__('Profile')"
    :description="__('Keep your name and login email up to date.')"
    :inline-errors="['name', 'email', 'current_password']"
>
    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $user->hasVerifiedEmail())
        <hw:alert>
            <hw:alert.title>{{ __('Your email is not verified yet.') }}</hw:alert.title>
            <hw:alert.description>
                <hw:form :action="route('verification.send')" method="POST" frame="_top" class="pt-3">
                    <x-auth.submit-button :label="__('Resend verification email')" variant="outline" />
                </hw:form>
            </hw:alert.description>
        </hw:alert>
    @endif
    <hw:form :action="route('user-profile-information.update')" method="PUT" frame="_top" error-focus error-scroll>
        <hw:field.group>
            <hw:field name="name" :label="__('Name')">
                <hw:input :value="$user->name" autocomplete="name" maxlength="255" required />
            </hw:field>
            <hw:field name="email" :label="__('Email')">
                <hw:input type="email" :value="$user->email" autocomplete="username" maxlength="255" required />
            </hw:field>
            <hw:field name="current_password" :label="__('Current password')">
                <hw:input type="password" autocomplete="current-password" :old="false" />
                <hw:field.description>
                    {{ __('Required only when changing your email. Other sessions will be signed out and the new address must be verified.') }}
                </hw:field.description>
            </hw:field>
            <x-auth.submit-button :label="__('Save profile')" class="w-full" />
        </hw:field.group>
    </hw:form>
</x-layouts.settings>
