@props([
    'label' => null,
    'placeholder' => '',
    'icon' => null,
    'required' => false,
])

<div {{ $attributes->class(['flex flex-col gap-2']) }}>
    @if ($label)
        <label class="text-primary-text text-sm font-semibold">{{ $label }}</label>
    @endif
    <div class="relative">
        <input
            type="text"
            @class([
                'border-input-border min-h-[42px] w-full rounded-[5px] border pe-3 disabled:bg-secondary',
                'ps-[39px]' => isset($icon),
                'ps-3' => !isset($icon),
            ])
            {{ $attributes->wire('model') }}
            {{ $attributes->whereStartsWith('x-') }}
            placeholder="{{ $placeholder }}"
            @required($required)
        />
        @if ($icon)
            <span class="absolute left-[13px] top-1/2 -translate-y-1/2">
                <x-icons.search />
            </span>
        @endif
    </div>
</div>
