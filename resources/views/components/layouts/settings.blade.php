@props(['title', 'description' => null, 'inlineErrors' => []])

<x-layouts.dashboard
    :title="$title"
    :breadcrumbs="[
        ['label' => __('Dashboard'), 'href' => route('dashboard')],
        ['label' => __('Account settings'), 'href' => route('account.profile.edit')],
        ['label' => $title],
    ]"
    :flash-toasts="! $errors->any()"
    :prefetch="false"
>
    <section class="flex w-full max-w-3xl min-w-0 flex-col gap-6">
        <header class="flex flex-col gap-2">
            <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            @if ($description)
                <p class="text-muted-foreground text-sm">{{ $description }}</p>
            @endif
        </header>
        <div class="flex max-w-xl min-w-0 flex-col gap-6">
            <hw:navbar class="min-w-0" aria-label="{{ __('Account settings') }}">
                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updateProfileInformation()))
                    <hw:navbar.item
                        :href="route('account.profile.edit')"
                        :current="request()->routeIs('account.profile.*')"
                        data-turbo-prefetch="false"
                    >
                        {{ __('Profile') }}
                    </hw:navbar.item>
                @endif
                <hw:navbar.item
                    :href="Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::twoFactorAuthentication()) ? route('two-factor.settings') : route('account.password.edit')"
                    :current="request()->routeIs('account.password.*', 'two-factor.*')"
                    data-turbo-prefetch="false"
                >
                    {{ __('Security') }}
                </hw:navbar.item>
            </hw:navbar>
            <hw:separator />
            <x-auth.feedback :inline-errors="$inlineErrors" />
            {{ $slot }}
            @isset($footer)
                <div class="text-muted-foreground text-sm">{{ $footer }}</div>
            @endisset
        </div>
    </section>
</x-layouts.dashboard>
