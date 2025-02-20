@props([
    'label' => '',
    'placeholder' => 'Начните вводить',
    'icon' => null,
])

<div class="flex flex-col gap-2 mb-5">
    <label class="text-sm font-semibold text-primary-text">{{ $label }}</label>
    <div class="relative">
        <input class="border rounded-[5px] border-input-border min-h-[42px] ps-[39px] pe-3  w-full" type="text"
            {{ $attributes->wire('model') }} placeholder="{{ $placeholder }}" />
        @if ($icon)
            <span class="absolute left-[13px] top-1/2 -translate-y-1/2">
                <x-icons.search />
            </span>
        @endif
    </div>
</div>
