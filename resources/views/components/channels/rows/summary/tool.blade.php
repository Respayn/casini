@props(['params', 'bold' => false])

@php
    if (is_array($params)) {
        $params = collect($params);
    }
@endphp

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'font-bold' => $bold, 'min-w-28']) }}>
    <x-report.integration-badges :items="$params" />
</x-data.table-cell>
