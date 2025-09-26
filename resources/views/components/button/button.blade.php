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
    'navigate' => true,
])

@php
    // Если параметр square не передан, делаем кнопку квадратной, если label пустой
    $square ??= empty($label);
    $size = $variant === 'action' || $variant === 'implicit-action' ? 'xs-action' : $size;

    // Базовые классы, применяемые ко всем кнопкам
    $buttonClasses = [
        'inline-flex','gap-2','items-center','justify-center',
        'cursor-pointer','disabled:cursor-not-allowed','transition'
    ];

    // Классы, зависящие от варианта кнопки
    $buttonClasses[] = match ($variant) {
        'primary' => 'hover:not-disabled:bg-transparent bg-primary border-primary disabled:text-default-button-disabled hover:not-disabled:text-primary disabled:bg-secondary border text-white disabled:border-0',
        'ghost'   => 'text-default-button hover:not-disabled:bg-default-button disabled:text-default-button-disabled hover:not-disabled:text-white disabled:bg-secondary',
        'link'    => 'text-primary-text hover:text-primary items-center group hover:underline',
        'action'  => 'text-primary items-center group relative font-semibold relative',
        'implicit-action'  => 'text-secondary-text items-center group relative font-semibold relative',
        'outlined' => 'border border-input-border hover:border-primary active:bg-primary active:border-primary group text-secondary-text hover:text-primary active:text-white',
        default   => 'border-default-button text-default-button hover:not-disabled:bg-default-button disabled:text-default-button-disabled hover:not-disabled:text-white disabled:bg-secondary border disabled:border-0',
    };

    // TODO: добавить severity для изменения цветовой темы. Пример - https://primevue.org/button/#severity
    $buttonClasses[] = match ($size) {
        'none' => '',
        'xs' => 'h-6 text-sm rounded-md ' . ($square ? 'w-6' : 'px-3.5'),
        'xs-action' => 'h-6 rounded-md ' . ($square ? 'w-6' : 'px-3.5'),
        'sm' => 'h-8 rounded-md ' . ($square ? 'w-8' : 'px-3.5'),
        default => 'h-10 rounded-lg ' . ($square ? 'w-10' : 'px-3.5'),
    };

    if ($rounded) {
        $buttonClasses[] = '!rounded-full';
    }
@endphp

@if ($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => implode(' ', $buttonClasses)]) }}
       @if($navigate) wire:navigate @endif  {{-- ← условный wire:navigate --}}
    >
        @if ($icon) <x-dynamic-component class="{{ $iconClasses }}" :component="$icon" /> @endif
        @if ($label) <span>{{ $label }}</span> @endif
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => implode(' ', $buttonClasses)]) }}
            @if ($disabled) disabled @endif>
        @if ($icon) <x-dynamic-component class="{{ $iconClasses }}" :component="$icon" /> @endif
        @if($label)
            <span class="relative">
              <span class="{{ $variant === 'action' ? 'whitespace-nowrap' : '' }}">{{ $label }}</span>
              @if ($variant === 'action')
                <span class="absolute left-0 right-0 bottom-[2px] rounded-xl border-b border-primary" style="border-width: .5px;"></span>
              @endif
              @if ($variant === 'implicit-action')
                <span class="absolute left-0 right-0 bottom-[2px] rounded-xl border-b border-secondary-text" style="border-width: .5px;"></span>
              @endif
            </span>
        @endif
    </button>
@endif
