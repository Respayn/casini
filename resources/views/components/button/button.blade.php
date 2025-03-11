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
    // Если параметр square не передан, делаем кнопку квадратной, если label пустой
    $square ??= empty($label);
    $size = $variant === 'action' ? 'xs-action' : $size;

    // Базовые классы, применяемые ко всем кнопкам
    $buttonClasses = [
        'inline-flex',
        'items-center',
        'justify-center',
        'cursor-pointer',
        'disabled:cursor-not-allowed',
        'relative',
    ];

    // Классы, зависящие от варианта кнопки
    $buttonClasses[] = match ($variant) {
        'primary' => 'hover:not-disabled:bg-transparent bg-primary border-primary disabled:text-default-button-disabled hover:not-disabled:text-primary disabled:bg-secondary border text-white disabled:border-0',
        'ghost'   => 'text-default-button hover:not-disabled:bg-default-button disabled:text-default-button-disabled hover:not-disabled:text-white disabled:bg-secondary',
        'link'    => 'text-primary-text hover:text-primary items-center group',
        'action'  => 'text-primary items-center group relative font-semibold',
        default   => 'border-default-button text-default-button hover:not-disabled:bg-default-button disabled:text-default-button-disabled hover:not-disabled:text-white disabled:bg-secondary border disabled:border-0',
    };

    // Классы, зависящие от размера кнопки и формы (квадратная или стандартная)
    $buttonClasses[] = match ($size) {
        'none' => '',
        'xs' => 'h-6 text-sm rounded-md ' . ($square ? 'w-6' : 'px-3.5'),
        'xs-action' => 'h-6 rounded-md ' . ($square ? 'w-6' : 'px-3.5'),
        'sm' => 'h-8 rounded-md ' . ($square ? 'w-8' : 'px-3.5'),
        default => 'h-10 rounded-lg ' . ($square ? 'w-10' : 'px-3.5'),
    };

    // Если кнопка должна иметь круглую форму, добавляем класс rounded-full
    if ($rounded) {
        $buttonClasses[] = 'rounded-full';
    }
@endphp

@if ($href)
    <a href="{{ $href }}"
        {{ $attributes->merge(['class' => implode(' ', $buttonClasses)]) }}>
        @if ($icon)
            <x-dynamic-component class="mr-2 {{ $iconClasses }}" :component="$icon" />
        @endif
        {{ $label }}
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => implode(' ', $buttonClasses)]) }}
            @if ($disabled) disabled @endif>
        @if ($icon)
            <x-dynamic-component class="mr-2 {{ $iconClasses }}" :component="$icon" />
        @endif
        <span class="relative">
            <span>{{ $label }}</span>
            @if ($variant === 'action')
                <span class="absolute left-0 right-0 bottom-[2px] rounded-xl border-b border-primary" style="border-width: 0.5px;"></span>
            @endif
        </span>
    </button>
@endif
