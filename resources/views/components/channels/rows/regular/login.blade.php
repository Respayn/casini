@props(['params'])

<x-data.table-cell {{ $attributes }}>
    {{ $params ?? '-' }}
</x-data.table-cell>
