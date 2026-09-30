@props(['params', 'bold' => false])

@php
    $value = is_array($params) ? ($params['value'] ?? null) : $params;
    $format = is_array($params) ? ($params['format'] ?? null) : null;
    $code = is_array($params) ? ($params['code'] ?? null) : null;
    $parts = \App\Helpers\PlanValueHelper::planColumnParts($value, $format, $code, true);
@endphp

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'font-bold' => $bold]) }}>
    @if ($value !== null && $value !== '')
        <span>{{ $parts['value'] }}</span>
        @if ($parts['suffix'] !== null)
            <span class="text-xs font-normal text-secondary-text">{{ $parts['suffix'] }}</span>
        @endif
    @else
        -
    @endif
</x-data.table-cell>
