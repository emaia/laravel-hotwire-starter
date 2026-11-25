<x-layouts.auth
    :title="$recovery ? __('Use a recovery code') : __('Two-factor authentication')"
    :description="$recovery ? __('Enter one of the recovery codes you saved when enabling two-factor authentication.') : __('Enter the six-digit code from your authenticator app.')"
    :inline-errors="$recovery ? ['recovery_code'] : ['code']"
>
    <hw:form :action="route('two-factor.login.store')" method="POST" frame="_top" error-focus error-scroll>
        <hw:field.group>
            @if ($recovery)
                <hw:field name="recovery_code" :label="__('Recovery code')">
                    <hw:input autocomplete="off" :old="false" maxlength="100" required autofocus />
                </hw:field>
            @else
                <hw:field name="code" :label="__('Authentication code')">
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
            @endif
            <x-auth.submit-button :label="__('Verify and continue')" class="w-full" />
        </hw:field.group>
    </hw:form>
    <x-slot:footer>
        <a
            href="{{ route('two-factor.login', ['recovery' => $recovery ? 0 : 1]) }}"
            class="underline underline-offset-4"
        >
            {{ $recovery ? __('Use an authenticator code instead') : __('Use a recovery code instead') }}
        </a>
    </x-slot:footer>
</x-layouts.auth>
