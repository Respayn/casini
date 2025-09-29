@props([
    'color' => null,
    'icon' => null,
    'iconClasses' => null
])

@php
    $classes = 'inline-flex items-center whitespace-nowrap px-1.5 py-1 rounded-2xl ';

    $classes .= match ($color) {
        default => 'text-zinc-700 bg-zinc-400/15',
        'green' => 'text-[#30513A] bg-[#EBFCF0]',
        'red' => 'text-[#513030] bg-[#FCEBEB]'
     };
@endphp

<div {{ $attributes->class($classes) }}>
    @if (is_string($icon) && $icon !== '')
        <x-dynamic-component :component="'icons.' . $icon" class="{{ $iconClasses }}" />
    @endif

    {{ $slot }}
</div>
