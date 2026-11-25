@props(['filtersLayout' => 'sidebar', 'filtersLabel' => null, 'filtersOpen' => true])

<div
    {{
        $attributes->class([
            'grid min-w-0 items-start gap-6',
            'xl:has-[details[open]]:grid-cols-[14rem_minmax(0,1fr)]' => isset($filters) && $filtersLayout === 'sidebar',
        ])
    }}
    data-listing-layout="{{ $filtersLayout }}"
>
    @isset($filters)
        @if ($filtersLayout === 'toolbar')
            <section
                aria-label="{{ $filtersLabel ?? __('Search and filters') }}"
                class="bg-card text-card-foreground min-w-0 rounded-xl border p-4"
                data-listing-filters
            >
                {{ $filters }}
            </section>
        @else
            <details
                @if ($filtersOpen) open @endif
                class="bg-card text-card-foreground min-w-0 rounded-xl border"
                data-listing-filters
            >
                <summary class="focus-visible:ring-ring cursor-pointer rounded-xl p-4 font-semibold focus-visible:ring-2 focus-visible:outline-none">
                    {{ $filtersLabel ?? __('Search and filters') }}
                </summary>
                <div class="border-t p-4">{{ $filters }}</div>
            </details>
        @endif
    @endisset
    <div class="min-w-0">{{ $slot }}</div>
</div>
