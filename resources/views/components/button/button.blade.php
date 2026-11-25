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
    'target' => '_self',
    'loading' => null,
    'wrap' => false,
])
@php
    // Если параметр square не передан, делаем кнопку квадратной, если label пустой
    $square ??= empty($label) && $slot->isEmpty();
    // HTML-атрибут wrap без значения приходит как строка "wrap"
    $allowWrap = $wrap === true || $wrap === 1 || $wrap === '1' || $wrap === 'true' || $wrap === 'wrap';
    $isAction = in_array($variant, ['action', 'implicit-action']);
    if ($allowWrap) {
        $size = 'none';
    } elseif ($isAction) {
        $size = 'xs-action';
    }
    $hasLoading = filled($loading);

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

    // При wrap не рисуем отдельную полоску: она растягивается на ширину колонки.
    // Подчёркивание - обычное по буквам (box-decoration-clone), как 14.08.2026.
    $underlineColor = $allowWrap ? null : match ($variant) {
        'action' => 'border-primary',
        'implicit-action' => 'border-secondary-text',
        default => null,
    };

    $classes = [
        'cursor-pointer transition',
        'disabled:cursor-not-allowed' => !$href,
        $variantClasses,
        $sizeClasses,
        '!rounded-full' => $rounded && ! $allowWrap,
        'inline-flex gap-2 items-center justify-center' => ! $allowWrap,
        'inline-block max-w-full p-0 h-auto text-left bg-transparent border-0 appearance-none whitespace-normal align-top' => $allowWrap,
    ];

    $useSpaNavigation = $target !== '_blank';
@endphp

@if ($href)
    <a href="{{ $href }}" @if($useSpaNavigation) wire:navigate @endif {{ $attributes->class($classes) }} target="{{ $target }}">
@else
    <button
        type="{{ $type }}"
        {{ $attributes->class($classes) }}
        @disabled($disabled)
        @if ($hasLoading)
            wire:loading.class="pointer-events-none"
            wire:target="{{ $loading }}"
        @endif
    >
@endif
    @if ($hasLoading && $icon && ! $allowWrap)
        <span class="relative inline-flex size-5 shrink-0 items-center justify-center">
            <x-dynamic-component
                :component="$icon"
                @class(['size-5', $iconClasses])
                wire:loading.remove
                wire:target="{{ $loading }}"
            />
            <x-icons.progress-activity
                class="absolute inset-0 size-5 animate-spin"
                wire:loading
                wire:target="{{ $loading }}"
            />
        </span>
    @elseif ($hasLoading && ! $allowWrap)
        <span
            class="relative inline-flex size-5 shrink-0 items-center justify-center"
            wire:loading
            wire:target="{{ $loading }}"
        >
            <x-icons.progress-activity class="size-5 animate-spin" />
        </span>
    @elseif ($icon && ! $allowWrap)
        <x-dynamic-component :component="$icon" @class([$iconClasses])/>
    @endif

    @if ($label || $slot->isNotEmpty())
        @if ($allowWrap)
            <span @class(['inline box-decoration-clone', 'underline' => $isAction])>
                @if ($label)
                    {{ $label }}
                @else
                    {{ $slot }}
                @endif
            </span>
        @else
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
    @endif

@if ($href)
    </a>
@else
    </button>
@endif
