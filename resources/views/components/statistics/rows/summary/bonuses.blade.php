@props(['params'])

@php
    $kind = is_array($params) ? ($params['kind'] ?? null) : null;
    $value = is_array($params) ? ($params['value'] ?? null) : (is_numeric($params) ? $params : null);
@endphp

<x-data.table-cell class="bg-table-summary-bg font-bold" {{ $attributes }}>
    @if ($kind === 'amount' && is_numeric($value))
        {{ \App\Helpers\MoneyDisplay::currency($value) }}
    @elseif (is_numeric($params))
        {{ \App\Helpers\MoneyDisplay::currency($params) }}
    @else
        -
    @endif
</x-data.table-cell>
