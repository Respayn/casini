@props([
    'icon' => null,
    'variant' => null,
    'label' => '',
    'rounded' => null,
    'disabled' => null,
    'href' => null,
    'size' => null,
    'iconClasses' => '',
    'square' => null,
    'type' => 'button',
])

@php
    $square ??= empty($label);

    $buttonClasses = ['inline-flex items-center justify-center cursor-pointer'];
    $buttonClasses[] = 'disabled:cursor-not-allowed';
    $buttonClasses[] = match ($variant) {
        'primary'
            => 'hover:not-disabled:bg-transparent bg-primary border-primary disabled:text-default-button-disabled hover:not-disabled:text-primary disabled:bg-secondary border text-white disabled:border-0',
        'ghost'
            => 'text-default-button hover:not-disabled:bg-default-button disabled:text-default-button-disabled hover:not-disabled:text-white disabled:bg-secondary',
        default
            => 'border-default-button text-default-button hover:not-disabled:bg-default-button disabled:text-default-button-disabled hover:not-disabled:text-white disabled:bg-secondary border disabled:border-0',
    };
    $buttonClasses[] = match ($size) {
        'xs' => 'h-6 text-sm rounded-md' . ' ' . ($square ? 'w-6' : 'px-3.5'),
        'sm' => 'h-8 rounded-md' . ' ' . ($square ? 'w-8' : 'px-3.5'),
        default => 'h-10 rounded-lg' . ' ' . ($square ? 'w-10' : 'px-3.5'),
    };
@endphp

@if ($rounded)
    @if ($href)
        <a
            class="border-input-border hover:border-primary active:bg-primary group inline-flex cursor-pointer rounded-full border p-[10px]"
            href="{{ $href }}"
        >
            @if ($icon)
                <x-dynamic-component
                    class="text-secondary-text group-hover:text-primary group-active:text-white"
                    :component="$icon"
                />
            @endif
            {{ $label }}
        </a>
    @else
        <button
            class="border-input-border hover:border-primary active:bg-primary group cursor-pointer rounded-full border p-[10px]"
        >
            @if ($icon)
                <x-dynamic-component
                    class="text-secondary-text group-hover:text-primary group-active:text-white"
                    :component="$icon"
                />
            @endif
            {{ $label }}
        </button>
    @endif
@elseif ($variant === 'link')
    <div
        class="text-primary-text hover:text-primary group inline-flex cursor-pointer items-center"
        {{ $attributes->whereStartsWith('wire:click') }}
        {{ $attributes->whereStartsWith('x-on:') }}
    >
        @if ($icon)
            <x-dynamic-component
                class="mr-4"
                :component="$icon"
            />
        @endif
        <span class="font-semibold group-hover:underline">{{ $label }}</span>
    </div>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->class($buttonClasses) }}
        {{ $attributes }}
        @disabled($disabled)
    >
        @if ($icon)
            <x-dynamic-component
                @class([$iconClasses, 'mr-4' => !empty($label)])
                :component="$icon"
            />
        @endif
        {{ $label }}
    </button>
@endif
