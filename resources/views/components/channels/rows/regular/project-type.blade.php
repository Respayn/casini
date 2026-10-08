@props(['params'])

<x-data.table-cell {{ $attributes }}>
    {{ $params['name'] ?? '-' }}
</x-data.table-cell>
