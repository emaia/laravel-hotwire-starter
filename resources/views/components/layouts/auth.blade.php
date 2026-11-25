@props(['title', 'description' => null, 'wide' => false, 'inlineErrors' => []])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <hw:meta csrf color-scheme prefetch="false" cache="no-cache" />
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground antialiased">
    <main class="flex min-h-svh items-center justify-center p-6 md:p-10">
        <div @class(['flex w-full flex-col gap-6', 'max-w-sm' => ! $wide, 'max-w-xl' => $wide])>
            <a href="{{ route('home') }}" class="self-center text-sm font-medium">{{ config('app.name') }}</a>
            <hw:card>
                <hw:card.header>
                    <hw:card.title><h1>{{ $title }}</h1></hw:card.title>
                    @if ($description)
                        <hw:card.description>{{ $description }}</hw:card.description>
                    @endif
                </hw:card.header>
                <hw:card.content class="flex flex-col gap-6">
                    <x-auth.feedback :inline-errors="$inlineErrors" />
                    {{ $slot }}
                </hw:card.content>
            </hw:card>
            @isset($footer)
                <div class="text-muted-foreground text-center text-sm">{{ $footer }}</div>
            @endisset
        </div>
    </main>
</body>
</html>
