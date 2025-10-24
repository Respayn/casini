@props(['params'])

<x-data.table-cell {{ $attributes }}>
    {{ is_numeric($params) ? Number::currency($params, in: 'RUB', locale: 'ru', precision: 0) : '-' }}
</x-data.table-cell>
