@props(['paginator', 'layout' => 'stacked', 'frame' => null])

@php
    $pagination = (clone $paginator)->appends(request()->query())->onEachSide(1);
@endphp

@if ($pagination->hasPages())
    <div
        {{
            $attributes->class([
                'flex gap-3 border-t pt-4',
                'flex-wrap items-center justify-between' => $layout === 'split',
                'flex-col items-center' => $layout !== 'split',
            ])
        }}
        data-listing-pagination
    >
        <p @class(['text-muted-foreground shrink-0', 'text-sm' => $layout === 'split', 'order-last text-center text-xs' => $layout !== 'split'])>
            {{ __(':first–:last of :total results', ['first' => $pagination->firstItem() ?? 0, 'last' => $pagination->lastItem() ?? 0, 'total' => $pagination->total()]) }}
        </p>
        <div @class(['flex items-center justify-between gap-2 sm:hidden', 'ml-auto' => $layout === 'split', 'w-full' => $layout !== 'split'])>
            <hw:pagination :label="__('Pagination')">
                <hw:pagination.content class="w-full justify-between">
                    <hw:pagination.item>
                        <hw:pagination.previous
                            :href="$pagination->previousPageUrl()"
                            :frame="$frame"
                            label=""
                            :aria-label="__('Previous page')"
                        />
                    </hw:pagination.item>
                    <hw:pagination.item>
                        <span class="text-muted-foreground text-sm" aria-current="page">
                            {{ __('Page :current of :last', ['current' => $pagination->currentPage(), 'last' => $pagination->lastPage()]) }}
                        </span>
                    </hw:pagination.item>
                    <hw:pagination.item>
                        <hw:pagination.next
                            :href="$pagination->nextPageUrl()"
                            :frame="$frame"
                            label=""
                            :aria-label="__('Next page')"
                        />
                    </hw:pagination.item>
                </hw:pagination.content>
            </hw:pagination>
        </div>
        <div @class(['hidden sm:block', 'ml-auto' => $layout === 'split'])>
            <hw:pagination
                :paginator="$pagination"
                :frame="$frame"
                :label="__('Pagination')"
                :previous-label="null"
                :next-label="null"
                :previous-aria-label="__('Previous page')"
                :next-aria-label="__('Next page')"
                :ellipsis-label="__('More pages')"
            />
        </div>
    </div>
@endif
