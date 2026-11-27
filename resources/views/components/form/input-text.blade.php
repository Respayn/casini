@props([
    'label' => null,
    'placeholder' => '',
    'icon' => null,
    'required' => false,
    'disabled' => false,
    'mask' => null,
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $isPhoneMask = $mask === 'phone';
    $hasError = $wireModel && $errors->has($wireModel);

    $ssrValue = $attributes->get('value');
    if ($ssrValue === null && filled($wireModel)) {
        try {
            $livewire = \Livewire\Livewire::current();
            if ($livewire) {
                $ssrValue = data_get($livewire, $wireModel);
            }
        } catch (\Throwable $e) {
            $ssrValue = null;
        }
    }
@endphp

<div {{ $attributes->class(['flex flex-col gap-2'])->only(['class', 'style']) }}>
    @if ($label)
        <label class="text-primary-text text-sm font-semibold">{{ $label }}</label>
    @endif
    <div class="relative">
        <input
            type="{{ $attributes->get('type') ?? 'text' }}"
            @class([
                'min-h-[42px] w-full rounded-[5px] border pe-3',
                'border-input-border',
                'border-warning-red' => $hasError,
                'ps-[39px]' => isset($icon),
                'ps-3' => ! isset($icon),
                'disabled:bg-secondary' => $disabled,
            ])
            placeholder="{{ $placeholder }}"
            @required($required)
            @disabled($disabled)
            @if ($isPhoneMask)
                x-mask="+7 (999) 999-99-99"
                inputmode="tel"
                autocomplete="tel"
            @endif
            @if ($ssrValue !== null && ! $attributes->has('value'))
                value="{{ $ssrValue }}"
            @endif
            {{ $attributes->except(['class', 'style', 'type']) }}
        />
        @if ($icon)
            <span class="pointer-events-none absolute left-[13px] top-1/2 -translate-y-1/2">
                <x-dynamic-component :component="$icon" class="h-5 w-5 text-gray-400" />
            </span>
        @endif
    </div>
    @if ($wireModel)
        @error($wireModel)
            <span class="text-warning-red text-[12px]">{{ $message }}</span>
        @enderror
    @endif
</div>
