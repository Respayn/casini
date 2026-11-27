@props(['params'])

<x-data.table-cell
    class="bg-table-summary-bg"
    {{ $attributes }}
>
    <span class="font-bold">{{ $params['count'] }}</span>
</x-data.table-cell>
