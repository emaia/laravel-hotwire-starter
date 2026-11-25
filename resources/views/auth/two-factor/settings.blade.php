@php
    $inlineErrors = match (true) {
        $user->two_factor_confirmed_at !== null => ['code', 'recovery_code'],
        $pending => ['code'],
        default => [],
    };
@endphp

<x-layouts.settings
    :title="__('Account security')"
    :description="__('Add an extra layer of protection with an authenticator app.')"
    :inline-errors="$inlineErrors"
>
    <div class="flex flex-col gap-6" data-turbo-temporary>
        <hw:button as="a" :href="route('account.password.edit')" variant="outline">
            {{ __('Change password') }}
        </hw:button>
        <hw:separator />
        @if ($user->two_factor_confirmed_at !== null)
            <section class="flex flex-col gap-4">
                <h2 class="font-medium">{{ __('Two-factor authentication is enabled') }}</h2>
                <p class="text-muted-foreground text-sm">
                    {{ __('Your next login will require a code from your app or a recovery code.') }}
                </p>
                <hw:button as="a" :href="route('two-factor.recovery')" variant="outline" data-turbo-prefetch="false">
                    {{ __('View recovery codes') }}
                </hw:button>
            </section>
            <hw:separator />
            <section class="flex flex-col gap-4">
                <h2 class="font-medium">{{ __('Disable two-factor authentication') }}</h2>
                <p class="text-muted-foreground text-sm">
                    {{ __('Confirm possession of your authenticator. Other sessions will be signed out.') }}
                </p>
                <hw:form :action="route('two-factor.disable')" method="DELETE" frame="_top" error-focus error-scroll>
                    <hw:field.group>
                        @include('auth.two-factor._proof')
                        <x-auth.submit-button :label="__('Disable two-factor authentication')" variant="destructive" />
                    </hw:field.group>
                </hw:form>
            </section>
        @elseif ($pending)
            <section class="flex flex-col gap-4">
                <h2 class="font-medium">{{ __('1. Add this account to your authenticator') }}</h2>
                <div
                    class="bg-background w-fit max-w-full overflow-hidden rounded-lg border p-4"
                    role="img"
                    aria-label="{{ __('Authenticator setup QR code') }}"
                >
                    {!! $qrCode !!}
                </div>
                <p class="text-muted-foreground text-sm">
                    {{ __('Scan the QR code or enter this setup key manually:') }}
                </p>
                {{-- prettier-ignore --}}
                <code class="bg-muted text-foreground rounded-md p-3 text-sm break-all" data-two-factor-secret>{{ $secret }}</code>
            </section>
            <hw:form :action="route('two-factor.confirm')" method="POST" frame="_top" error-focus error-scroll>
                <hw:field.group>
                    <hw:field name="code" :label="__('2. Confirm a code from your app')">
                        <hw:input
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            autocomplete="one-time-code"
                            :old="false"
                            required
                            autofocus
                        />
                    </hw:field>
                    <x-auth.submit-button :label="__('Confirm and enable')" class="w-full" />
                </hw:field.group>
            </hw:form>
            <hw:form :action="route('two-factor.disable')" method="DELETE" frame="_top">
                <x-auth.submit-button :label="__('Cancel setup')" variant="outline" class="w-full" />
            </hw:form>
        @else
            <p class="text-muted-foreground text-sm">
                {{ __('Use any TOTP-compatible app. You will also receive recovery codes to keep somewhere safe.') }}
            </p>
            <hw:form :action="route('two-factor.enable')" method="POST" frame="_top">
                <x-auth.submit-button :label="__('Set up authenticator')" class="w-full" />
            </hw:form>
        @endif
    </div>
</x-layouts.settings>
