@props(['params'])

<x-data.table-cell class="bg-table-summary-bg" {{ $attributes }}>
    {{ is_numeric($params) ? Number::currency($params, in: 'RUB', locale: 'ru', precision: abs((float) $params - round((float) $params)) < 0.001 ? 0 : 2) : '-' }}
</x-data.table-cell>
