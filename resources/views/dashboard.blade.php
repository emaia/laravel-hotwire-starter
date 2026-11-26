@php
    $twoFactorEnabled = Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::twoFactorAuthentication());
    $passwordUpdatesEnabled = Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords());
@endphp

<x-layouts.dashboard>
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-2">
            <h1 class="text-3xl font-semibold tracking-tight">
                {{ __('Welcome, :name', ['name' => auth()->user()->name]) }}
            </h1>
            <p class="text-muted-foreground">{{ __('Your account is ready. This space is yours to build.') }}</p>
        </div>
        @if ($twoFactorEnabled || $passwordUpdatesEnabled)
            <div class="grid gap-6 md:grid-cols-2">
                @if ($twoFactorEnabled)
                    <hw:card>
                        <hw:card.header>
                            <hw:card.title><h2>{{ __('Account security') }}</h2></hw:card.title>
                            <hw:card.description>
                                {{ __('Protect your account with two-factor authentication.') }}
                            </hw:card.description>
                        </hw:card.header>
                        <hw:card.footer>
                            <hw:button
                                as="a"
                                :href="route('two-factor.settings')"
                                variant="outline"
                                data-turbo-prefetch="false"
                            >
                                {{ __('Manage security') }}
                            </hw:button>
                        </hw:card.footer>
                    </hw:card>
                @endif
                @if ($passwordUpdatesEnabled)
                    <hw:card>
                        <hw:card.header>
                            <hw:card.title><h2>{{ __('Password') }}</h2></hw:card.title>
                            <hw:card.description>
                                {{ __('Keep your credentials secure and up to date.') }}
                            </hw:card.description>
                        </hw:card.header>
                        <hw:card.footer>
                            <hw:button
                                as="a"
                                :href="route('account.password.edit')"
                                variant="outline"
                                data-turbo-prefetch="false"
                            >
                                {{ __('Change password') }}
                            </hw:button>
                        </hw:card.footer>
                    </hw:card>
                @endif
            </div>
        @endif
    </div>
</x-layouts.dashboard>
