@props([
    'label' => null,
    'placeholder' => '',
    'icon' => null,
    'required' => false,
])

@php
    $wireModel = $attributes->get('wire:model');
@endphp

<div {{ $attributes->class(['flex flex-col gap-2']) }}>
    @if ($label)
        <label class="text-primary-text text-sm font-semibold">{{ $label }}</label>
    @endif
    <div class="relative">
        <div class="flex flex-col justify-start">
            <input
                type="text"
                @class([
                    'min-h-[42px] w-full rounded-[5px] border pe-3 disabled:bg-secondary',
                    'border-input-border' => !$errors->has($wireModel),
                    'border-warning-red' => $errors->has($wireModel),
                    'ps-[39px]' => isset($icon),
                    'ps-3' => !isset($icon),
                ])
                {{ $attributes->wire('model') }}
                {{ $attributes->whereStartsWith('x-') }}
                placeholder="{{ $placeholder }}"
                @required($required)
            />
            @error($wireModel)
            <span class="text-warning-red text-[12px]">{{ $message }}</span>
            @enderror
        </div>
        @if ($icon)
            <span class="absolute left-[13px] top-1/2 -translate-y-1/2">
                <x-icons.search />
            </span>
        @endif
    </div>
</div>
