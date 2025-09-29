@props(['params'])

<x-data.table-cell class="bg-table-summary-bg" {{ $attributes }}>
    {{ $params ? Number::currency($params, in: 'RUB', locale: 'ru') : '-' }}
</x-data.table-cell>
