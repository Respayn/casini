@props([
    'href' => null,
])

@php
    $classes = 'mb-4 inline-flex max-h-[26px] items-center gap-3 text-[18px] font-normal text-secondary-text hover:text-caption-text';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }} wire:navigate>
        <x-icons.arrow-left class="h-5 w-5" />
        <span>Назад</span>
    </a>
@else
    <button type="button" {{ $attributes->class([$classes]) }}>
        <x-icons.arrow-left class="h-5 w-5" />
        <span>Назад</span>
    </button>
@endif
