<x-layouts.app :title="__('Listing')">
    <turbo-frame id="listing-results" data-turbo-action="advance">
        <x-listing-layout :filters-layout="$filtersLayout">
            <x-slot:filters>
                <x-listing-filters :action="url('/_test/listings')" frame="listing-results">
                    <hw:field name="search" :label="__('Search')">
                        <hw:input :value="$search" />
                    </hw:field>
                </x-listing-filters>
            </x-slot:filters>
            <section class="flex min-w-0 flex-col gap-4" aria-label="{{ __('Results') }}">
                <ul>
                    @forelse ($records as $record)
                        <li>{{ $record }}</li>
                    @empty
                        <li>{{ __('No results found.') }}</li>
                    @endforelse
                </ul>
                <x-listing-pagination :paginator="$records" :layout="$paginationLayout" frame="listing-results" />
            </section>
        </x-listing-layout>
    </turbo-frame>
</x-layouts.app>
