<x-layouts.auth
    :title="__('Verify your email')"
    :description="__('Open the verification link sent to your email to continue to the dashboard.')"
>
    <p class="text-muted-foreground text-sm break-all">{{ auth()->user()->email }}</p>
    <hw:form :action="route('verification.send')" method="POST" frame="_top">
        <x-auth.submit-button :label="__('Resend verification email')" class="w-full" />
    </hw:form>
    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updateProfileInformation()))
        <hw:button
            as="a"
            :href="route('account.profile.edit')"
            variant="outline"
        >{{ __('Correct my email') }}</hw:button>
    @endif
    <hw:form :action="route('logout')" method="POST" frame="_top">
        <x-auth.submit-button :label="__('Log out')" variant="ghost" class="w-full" />
    </hw:form>
</x-layouts.auth>
