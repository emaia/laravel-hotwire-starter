<x-layouts.app>
    <div class="flex flex-col gap-12 md:gap-16">
        <section class="flex max-w-3xl flex-col items-start gap-6">
            <hw:badge variant="secondary">Laravel + Hotwire</hw:badge>
            <h1 class="text-4xl font-semibold tracking-tight text-balance md:text-6xl">
                {{ __('Your next idea. Built with Blade.') }}
            </h1>
            <p class="text-muted-foreground max-w-2xl text-lg leading-relaxed text-pretty">
                {{ __('A starter kit for reactive Laravel apps. Keep your routes, render HTML on the server, and add JavaScript only where you need it.') }}
            </p>
            <div class="flex flex-wrap gap-3">
                <hw:button as="a" href="https://hotwire.emaia.dev/installation">
                    {{ __('Explore Laravel Hotwire') }}
                </hw:button>
                <hw:button as="a" href="https://hotwire.emaia.dev/components" variant="outline">
                    {{ __('Browse components') }}
                </hw:button>
            </div>
        </section>
        <section class="grid gap-6 md:grid-cols-3" aria-label="{{ __('Your Hotwire toolkit') }}">
            <hw:card class="min-w-0 md:row-span-3 md:grid md:grid-rows-subgrid">
                <hw:card.header>
                    <hw:card.title><h2>{{ __('Blade components') }}</h2></hw:card.title>
                    <hw:card.description>{{ __('Build the interface, not the plumbing.') }}</hw:card.description>
                </hw:card.header>
                <hw:card.content>
                    <p class="text-muted-foreground text-sm leading-relaxed">
                        {{ __('Forms, dialogs and feedback that work with Laravel validation and your theme from the start.') }}
                    </p>
                </hw:card.content>
                <hw:card.footer>
                    <hw:button
                        as="a"
                        href="https://hotwire.emaia.dev/components"
                        variant="link"
                        class="h-auto min-h-8 w-full min-w-0 justify-start px-0 text-left whitespace-normal"
                    >
                        {{ __('Discover components') }}
                    </hw:button>
                </hw:card.footer>
            </hw:card>
            <hw:card class="min-w-0 md:row-span-3 md:grid md:grid-rows-subgrid">
                <hw:card.header>
                    <hw:card.title><h2>Turbo</h2></hw:card.title>
                    <hw:card.description>{{ __('Fast navigation. Server-rendered HTML.') }}</hw:card.description>
                </hw:card.header>
                <hw:card.content>
                    <p class="text-muted-foreground text-sm leading-relaxed">
                        {{ __('Use Drive, Frames and Streams to update pages without maintaining a separate frontend application.') }}
                    </p>
                </hw:card.content>
                <hw:card.footer>
                    <hw:button
                        as="a"
                        href="https://hotwire.emaia.dev/server-driven-ui"
                        variant="link"
                        class="h-auto min-h-8 w-full min-w-0 justify-start px-0 text-left whitespace-normal"
                    >
                        {{ __('Explore server-driven UI') }}
                    </hw:button>
                </hw:card.footer>
            </hw:card>
            <hw:card class="min-w-0 md:row-span-3 md:grid md:grid-rows-subgrid">
                <hw:card.header>
                    <hw:card.title><h2>Stimulus</h2></hw:card.title>
                    <hw:card.description>{{ __('Small behaviors. Loaded on demand.') }}</hw:card.description>
                </hw:card.header>
                <hw:card.content>
                    <p class="text-muted-foreground text-sm leading-relaxed">
                        {{ __('Add focused interactions with HTML attributes and lazy-loaded controllers. Extend only what your app needs.') }}
                    </p>
                </hw:card.content>
                <hw:card.footer>
                    <hw:button
                        as="a"
                        href="https://hotwire.emaia.dev/controllers"
                        variant="link"
                        class="h-auto min-h-8 w-full min-w-0 justify-start px-0 text-left whitespace-normal"
                    >
                        {{ __('Meet the controllers') }}
                    </hw:button>
                </hw:card.footer>
            </hw:card>
        </section>
        <hw:card>
            <hw:card.header>
                <hw:card.title><h2>{{ __('A foundation you can make your own') }}</h2></hw:card.title>
                <hw:card.description>
                    {{ __('Authentication, two-factor security and a themeable UI. No business rules to undo.') }}
                </hw:card.description>
            </hw:card.header>
            <hw:card.content class="flex flex-wrap gap-3">
                <hw:badge variant="outline">Blade</hw:badge>
                <hw:badge variant="outline">Turbo</hw:badge>
                <hw:badge variant="outline">Stimulus</hw:badge>
                <hw:badge variant="outline">Tailwind CSS</hw:badge>
            </hw:card.content>
        </hw:card>
    </div>
</x-layouts.app>
