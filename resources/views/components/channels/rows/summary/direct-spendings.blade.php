@props(['params', 'bold' => false])

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'whitespace-nowrap', 'font-bold' => $bold]) }}>
    {{ $params === null ? '-' : Number::currency($params, in: 'RUB', locale: 'ru') }}
</x-data.table-cell>
