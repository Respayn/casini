@props(['params', 'bold' => false])

@php
    $value = is_array($params) ? ($params['value'] ?? null) : $params;
    $format = is_array($params) ? ($params['format'] ?? null) : null;
@endphp

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'font-bold' => $bold]) }}>
    @if ($value !== null && $value !== '')
        {{ \App\Helpers\PlanValueHelper::format($value, $format) }}
    @else
        -
    @endif
</x-data.table-cell>
