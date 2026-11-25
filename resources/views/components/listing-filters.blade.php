@props(['action' => null, 'clearUrl' => null, 'frame' => null])

<hw:form
    :action="$action ?? url()->current()"
    method="GET"
    :frame="$frame"
    {{ $attributes->class(['flex flex-col gap-4']) }}
>
    {{ $slot }}
    <div class="flex flex-wrap gap-2">
        <hw:button type="submit">{{ __('Apply filters') }}</hw:button>
        <hw:button as="a" :href="$clearUrl ?? $action ?? url()->current()" :frame="$frame" variant="outline">
            {{ __('Clear filters') }}
        </hw:button>
    </div>
</hw:form>
