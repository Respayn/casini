@props(['params'])

@php
    $kind = is_array($params) ? ($params['kind'] ?? null) : null;
    $value = is_array($params) ? ($params['value'] ?? null) : (is_numeric($params) ? $params : null);
@endphp

<x-data.table-cell class="bg-table-summary-bg font-bold" {{ $attributes }}>
    @if ($kind === 'amount' && is_numeric($value))
        {{ Number::currency($value, in: 'RUB', locale: 'ru', precision: 0) }}
    @elseif (is_numeric($params))
        {{ Number::currency($params, in: 'RUB', locale: 'ru', precision: 0) }}
    @else
        -
    @endif
</x-data.table-cell>
