@props(['params'])

<x-data.table-cell {{ $attributes }} class="whitespace-nowrap">
    {{ $params ?? '-' }}
</x-data.table-cell>
