@props(['params'])

<x-data.table-cell class="bg-table-summary-bg whitespace-nowrap" {{ $attributes }}>
    {{ $params === null ? '-' : Number::currency($params, in: 'RUB', locale: 'ru') }}
</x-data.table-cell>
