@props([
    /**
     * Список Livewire-целей через запятую (свойства / методы), при которых показываем скелетон.
     * Не включать saveSettingsSnapshot — иначе мигание при открытии шестерёнки.
     * Не включать refreshAllData / pollRefreshProgress — ручной съём рисует скелет по строкам.
     */
    'targets' => 'queryData.showInactive, queryData.includeVat, queryData.dateFrom, queryData.dateTo, applySettingsSnapshot',
])

@php
    $targets = is_array($targets) ? implode(', ', $targets) : (string) $targets;
@endphp

<x-report.table-loading-bars />

<div
    {{ $attributes->class(['relative mt-3'])->merge([
        'style' => 'min-height: 240px; '.$attributes->get('style'),
    ]) }}
    wire:loading.class="report-table-loading"
    wire:target="{{ $targets }}"
>
    {{ $slot }}
</div>
