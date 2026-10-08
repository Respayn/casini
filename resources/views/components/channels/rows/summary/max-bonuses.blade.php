@props(['params', 'bold' => false])

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'font-bold' => $bold]) }}>
    {{ is_numeric($params) ? Number::currency($params, in: 'RUB', locale: 'ru', precision: abs((float) $params - round((float) $params)) < 0.001 ? 0 : 2) : '-' }}
</x-data.table-cell>
