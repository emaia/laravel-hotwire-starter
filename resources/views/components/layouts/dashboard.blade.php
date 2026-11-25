@props(['title' => __('Dashboard'), 'breadcrumbs' => [], 'padded' => true, 'flashToasts' => true, 'prefetch' => true])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <hw:meta csrf color-scheme :prefetch="$prefetch" refresh scroll="preserve" cache="no-cache" />
    {{ $meta ?? '' }}
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground min-h-svh antialiased">
    <hw:sidebar.provider>
        <hw:sidebar collapsible="icon" variant="inset">
            <hw:sidebar.header>
                <hw:sidebar.brand :href="route('home')" :label="config('app.name')">
                    <span class="flex items-center gap-3 py-2">
                        <hw:icon name="app-window" class="size-6 shrink-0" aria-hidden="true" />
                        <span class="grid min-w-0 text-left text-sm leading-tight">
                            <span class="truncate font-semibold">{{ config('app.name') }}</span>
                            <span class="text-muted-foreground truncate text-xs">{{ __('Starter kit') }}</span>
                        </span>
                    </span>
                    <x-slot:icon>
                        <hw:icon name="app-window" class="size-6" aria-hidden="true" />
                    </x-slot:icon>
                </hw:sidebar.brand>
            </hw:sidebar.header>

            <hw:sidebar.content>
                <nav aria-label="{{ __('Main navigation') }}">
                    <hw:sidebar.group>
                        <hw:sidebar.group-content>
                            <hw:sidebar.menu>
                                <hw:sidebar.menu-item>
                                    <hw:sidebar.menu-button
                                        :href="route('dashboard')"
                                        :active="request()->routeIs('dashboard')"
                                        :aria-current="request()->routeIs('dashboard') ? 'page' : false"
                                        :tooltip="__('Dashboard')"
                                    >
                                        <hw:icon name="app-window" aria-hidden="true" />
                                        <span>{{ __('Dashboard') }}</span>
                                    </hw:sidebar.menu-button>
                                </hw:sidebar.menu-item>
                            </hw:sidebar.menu>
                        </hw:sidebar.group-content>
                    </hw:sidebar.group>
                    <hw:sidebar.group>
                        <hw:sidebar.group-label>{{ __('Resources') }}</hw:sidebar.group-label>
                        <hw:sidebar.group-content>
                            <hw:sidebar.menu>
                                <hw:sidebar.menu-item>
                                    <hw:sidebar.menu-button
                                        href="https://hotwire.emaia.dev"
                                        :tooltip="__('Hotwire documentation')"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <hw:icon name="code" aria-hidden="true" />
                                        <span>{{ __('Documentation') }}</span>
                                    </hw:sidebar.menu-button>
                                </hw:sidebar.menu-item>
                            </hw:sidebar.menu>
                        </hw:sidebar.group-content>
                    </hw:sidebar.group>
                </nav>
            </hw:sidebar.content>

            <hw:sidebar.footer>
                <hw:sidebar.menu>
                    <hw:sidebar.menu-item>
                        <hw:dropdown class="w-full">
                            <hw:dropdown.trigger as-child>
                                <hw:sidebar.menu-button size="lg" class="w-full" :aria-label="__('Account menu')">
                                    <hw:avatar :name="auth()->user()->name" />
                                    <span class="pointer-events-none grid min-w-0 flex-1 text-left text-sm leading-tight">
                                        <span class="truncate font-medium">{{ auth()->user()->name }}</span>
                                        <span class="text-muted-foreground truncate text-xs">
                                            {{ auth()->user()->email }}
                                        </span>
                                    </span>
                                    <hw:icon name="chevron-down" class="ml-auto shrink-0" aria-hidden="true" />
                                </hw:sidebar.menu-button>
                            </hw:dropdown.trigger>
                            <hw:dropdown.content
                                side="right"
                                align="end"
                                collapsed-side="right"
                                collapsed-align="end"
                                mobile-side="top"
                                mobile-align="start"
                                width="w-(--anchor-width) min-w-56"
                            >
                                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updateProfileInformation()))
                                    <hw:dropdown.item
                                        :href="route('account.profile.edit')"
                                        frame="_top"
                                        data-turbo-prefetch="false"
                                    >
                                        {{ __('Profile') }}
                                    </hw:dropdown.item>
                                @endif
                                @if (Route::has('user-password.update'))
                                    <hw:dropdown.item
                                        :href="route('account.password.edit')"
                                        frame="_top"
                                        data-turbo-prefetch="false"
                                    >
                                        {{ __('Change password') }}
                                    </hw:dropdown.item>
                                @endif
                                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::twoFactorAuthentication()))
                                    <hw:dropdown.item
                                        :href="route('two-factor.settings')"
                                        frame="_top"
                                        data-turbo-prefetch="false"
                                    >
                                        {{ __('Account security') }}
                                    </hw:dropdown.item>
                                @endif
                                <hw:dropdown.separator />
                                <hw:form :action="route('logout')" method="POST" frame="_top">
                                    <hw:dropdown.item type="submit" variant="destructive" class="w-full">
                                        {{ __('Log out') }}
                                    </hw:dropdown.item>
                                </hw:form>
                            </hw:dropdown.content>
                        </hw:dropdown>
                    </hw:sidebar.menu-item>
                </hw:sidebar.menu>
            </hw:sidebar.footer>
            <hw:sidebar.rail />
        </hw:sidebar>

        <hw:sidebar.inset class="min-w-0">
            <header
                data-dashboard-topbar
                class="border-sidebar-border/50 flex h-16 shrink-0 items-center gap-2 border-b px-4 transition-[width,height] ease-linear md:group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
            >
                <hw:sidebar.trigger :label="__('Toggle sidebar')" />
                <div class="min-w-0 flex-1">
                    @isset($topbar)
                        {{ $topbar }}
                    @else
                        <hw:breadcrumb
                            :items="$breadcrumbs ?: [['label' => $title]]"
                            :label="__('Breadcrumb')"
                            collapse-below="md"
                        />
                    @endisset
                </div>
                <hw:color-scheme.toggle
                    :aria-label="__('Toggle color scheme')"
                    :tooltip="__('Toggle color scheme')"
                    modes="light dark system"
                    view-transition
                />
            </header>
            <main @class(['flex min-w-0 flex-1 flex-col', 'gap-6 p-6' => $padded])>{{ $slot }}</main>
        </hw:sidebar.inset>
    </hw:sidebar.provider>

    <hw:drawer frame="drawer-panel" direction="right">
        <x-slot:loading_template>
            <div class="p-6"><hw:spinner aria-label="{{ __('Loading') }}" /></div>
        </x-slot:loading_template>
    </hw:drawer>
    <hw:sheet frame="settings-panel" side="right">
        <x-slot:loading_template>
            <div class="flex flex-col gap-4 p-6 py-20">
                <hw:skeleton class="h-4 w-3/4" />
                <hw:skeleton class="h-4 w-full" />
                <hw:skeleton class="h-4 w-full" />
            </div>
        </x-slot:loading_template>
    </hw:sheet>
    <hw:modal size="auto" frame="modal">
        <x-slot:loading_template>
            <div class="flex items-center justify-center p-8">
                <hw:spinner aria-label="{{ __('Loading') }}" />
            </div>
        </x-slot:loading_template>
    </hw:modal>
    <hw:toaster position="top-end" :flash="$flashToasts" />
</body>
</html>
