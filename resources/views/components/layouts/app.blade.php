@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <hw:meta csrf color-scheme prefetch refresh cache="no-cache" />
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground min-h-svh antialiased">
    <div class="mx-auto flex min-h-svh max-w-5xl flex-col gap-12 px-6 py-6 md:gap-20 md:px-10">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="font-semibold tracking-tight">{{ config('app.name') }}</a>
            <nav
                class="flex max-w-full min-w-0 flex-wrap items-center gap-2 sm:gap-3"
                aria-label="{{ __('Main navigation') }}"
            >
                <hw:button as="a" href="https://hotwire.emaia.dev" variant="ghost" size="sm">
                    {{ __('Documentation') }}
                </hw:button>
                @auth
                    <hw:button as="a" :href="route('dashboard')" variant="outline" size="sm">
                        {{ __('Dashboard') }}
                    </hw:button>
                    <hw:form :action="route('logout')" method="POST" frame="_top">
                        <hw:button type="submit" variant="ghost" size="sm">{{ __('Log out') }}</hw:button>
                    </hw:form>
                @else
                    <hw:button as="a" :href="route('login')" variant="outline" size="sm">{{ __('Log in') }}</hw:button>
                    @if (Route::has('register'))
                        <hw:button as="a" :href="route('register')" size="sm">{{ __('Get started') }}</hw:button>
                    @endif
                @endauth
                <hw:color-scheme.toggle
                    :aria-label="__('Toggle color scheme')"
                    :tooltip="__('Toggle color scheme')"
                    modes="light dark system"
                    view-transition
                />
            </nav>
        </header>
        <main class="flex-1">{{ $slot }}</main>
        <footer class="text-muted-foreground flex flex-wrap items-center justify-between gap-3 border-t py-6 text-sm">
            <span>{{ __('Built with Laravel, Blade and Hotwire.') }}</span>
            <a href="https://hotwire.emaia.dev/theming" class="underline-offset-4 hover:underline">
                {{ __('Make it your own') }}
            </a>
        </footer>
    </div>
</body>
</html>
