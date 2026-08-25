@props([
    'disabled' => false,
])

@php
    $isDisabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN);
@endphp

<label
    @class([
        'inline-flex items-center',
        'cursor-pointer' => ! $isDisabled,
        'cursor-not-allowed' => $isDisabled,
    ])
>
    <input
        class="peer sr-only"
        type="checkbox"
        @disabled($isDisabled)
        {{ $attributes->except('disabled') }}
    >
    <div
        @class([
            'bg-toggle-switch-bg after:bg-toggle-switch-handle-bg relative h-6 w-11 rounded-full after:absolute after:start-0 after:top-0 after:h-6 after:w-6 after:rounded-full after:transition-all after:content-[\'\'] peer-checked:after:translate-x-full peer-focus:outline-none',
            'peer-checked:after:bg-primary' => ! $isDisabled,
        ])
    ></div>
</label>
