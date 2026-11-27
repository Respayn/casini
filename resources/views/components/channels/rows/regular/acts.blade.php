@props(['params'])

<x-data.table-cell {{ $attributes }}>
    {{ $params !== null ? Number::currency($params, in: 'RUB', locale: 'ru') : '-' }}
</x-data.table-cell>
