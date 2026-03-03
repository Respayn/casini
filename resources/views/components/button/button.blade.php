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
    'target' => '_self'
])
@php
    // Если параметр square не передан, делаем кнопку квадратной, если label пустой
    $square ??= empty($label) && $slot->isEmpty();
    $isAction = in_array($variant, ['action', 'implicit-action']);
    $size = $isAction ? 'xs-action' : $size;

    // TODO: добавить severity для изменения цветовой темы. Пример - https://primevue.org/button/#severity
    $variantClasses = match ($variant) {
        'primary' => 'bg-primary border border-primary text-white hover:not-disabled:bg-transparent hover:not-disabled:text-primary disabled:bg-secondary disabled:border-0 disabled:text-default-button-disabled',
        'ghost' => 'text-default-button hover:not-disabled:bg-default-button hover:not-disabled:text-white disabled:bg-secondary disabled:text-default-button-disabled',
        'link' => 'text-primary-text hover:text-primary group hover:underline',
        'action' => 'text-primary group font-semibold',
        'implicit-action' => 'text-secondary-text group font-semibold',
        'outlined' => 'border border-input-border text-secondary-text hover:border-primary hover:text-primary active:bg-primary active:border-primary active:text-white group',
        default => 'border border-default-button text-default-button hover:not-disabled:bg-default-button hover:not-disabled:text-white disabled:bg-secondary disabled:border-0 disabled:text-default-button-disabled',
    };

    $sizeClasses = match ($size) {
        'none' => '',
        'xs' => 'h-6 text-sm rounded-md ' . ($square ? 'w-6' : 'px-3.5'),
        'xs-action' => 'h-6 rounded-md ' . ($square ? 'w-6' : 'px-3.5'),
        'sm' => 'h-8 rounded-md ' . ($square ? 'w-8' : 'px-3.5'),
        default => 'h-10 rounded-lg ' . ($square ? 'w-10' : 'px-3.5'),
    };

    $underlineColor = match ($variant) {
        'action' => 'border-primary',
        'implicit-action' => 'border-secondary-text',
        default => null,
    };

    $classes = [
        'inline-flex gap-2 items-center justify-center cursor-pointer transition',
        'disabled:cursor-not-allowed' => !$href,
        $variantClasses,
        $sizeClasses,
        '!rounded-full' => $rounded,
    ];

    $useSpaNavigation = $target !== '_blank';
@endphp

@if ($href)
    <a href="{{ $href }}" @if($useSpaNavigation) wire:navigate @endif {{ $attributes->class($classes) }} target="{{ $target }}">
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }} @disabled($disabled)>
@endif
    @if ($icon)
        <x-dynamic-component :component="$icon" @class([$iconClasses])/>
    @endif
        

    @if ($label || $slot->isNotEmpty())
        <span @class(['relative' => $underlineColor])>
            <span @class(['whitespace-nowrap' => $isAction])>
                @if ($label)
                    {{ $label }}
                @else
                    {{ $slot }}
                @endif
            </span>

        @if ($underlineColor)
            <span
            @class(['absolute bottom-[2px] left-0 right-0 rounded-xl border-b', $underlineColor])
                style="border-width: .5px;"
            ></span>
        @endif
        </span>
    @endif
    
@if ($href)
    </a>
@else
    </button>
@endif