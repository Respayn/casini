@props([
    /**
     * Список Livewire-целей через запятую (свойства / методы), при которых показываем скелетон.
     * Не включать saveSettingsSnapshot — иначе мигание при открытии шестерёнки.
     */
    'targets' => 'queryData.showInactive, queryData.includeVat, queryData.dateFrom, queryData.dateTo, refreshAllData, applySettingsSnapshot',
])

@php
    $targets = is_array($targets) ? implode(', ', $targets) : (string) $targets;
@endphp

{{-- Контент остаётся на месте; скелетон — оверлей без прыжка высоты --}}
<div
    {{ $attributes->class(['relative mt-3'])->merge([
        'style' => 'min-height: 240px; '.$attributes->get('style'),
    ]) }}
>
    <div
        wire:loading
        wire:target="{{ $targets }}"
        class="absolute inset-0 z-10 overflow-hidden"
        style="background-color: rgba(255, 255, 255, 0.75)"
    >
        <x-report.table-skeleton style="min-height: 100%" />
    </div>

    <div
        wire:loading.class="pointer-events-none opacity-40"
        wire:target="{{ $targets }}"
    >
        {{ $slot }}
    </div>
</div>
