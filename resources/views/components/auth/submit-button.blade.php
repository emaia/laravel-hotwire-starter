@props(['label', 'variant' => 'default', 'size' => 'default'])

<hw:button
    type="submit"
    :variant="$variant"
    :size="$size"
    :aria-label="$label"
    {{ $attributes->class(['relative form-busy:pointer-events-none']) }}
    data-auth-submit
>
    <span class="form-busy:invisible" data-auth-submit-label>{{ $label }}</span>
    <hw:spinner class="form-busy:block absolute inset-0 m-auto hidden size-4" aria-hidden="true" />
</hw:button>
