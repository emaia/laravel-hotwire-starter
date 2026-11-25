<x-layouts.settings
    :title="__('Recovery codes')"
    :description="__('Save these codes somewhere safe, separate from your authenticator device. Each code works once.')"
    :inline-errors="['code', 'recovery_code']"
>
    <div class="flex flex-col gap-6" data-turbo-temporary>
        <ul
            class="bg-muted grid gap-2 rounded-md p-4 font-mono text-sm sm:grid-cols-2"
            aria-label="{{ __('Your recovery codes') }}"
        >
            @foreach ($codes as $code)
                <li>{{ $code }}</li>
            @endforeach
        </ul>
        <hw:separator />
        <hw:form
            :action="route('two-factor.regenerate-recovery-codes')"
            method="POST"
            frame="_top"
            error-focus
            error-scroll
        >
            <hw:field.group>
                <h2 class="font-medium">{{ __('Generate new recovery codes') }}</h2>
                <p class="text-muted-foreground text-sm">
                    {{ __('This invalidates all previous recovery codes and signs out other sessions.') }}
                </p>
                @include('auth.two-factor._proof')
                <x-auth.submit-button :label="__('Generate new codes')" variant="outline" />
            </hw:field.group>
        </hw:form>
    </div>
    <x-slot:footer>
        <a href="{{ route('two-factor.settings') }}" class="underline underline-offset-4">
            {{ __('Back to account security') }}
        </a>
    </x-slot:footer>
</x-layouts.settings>
