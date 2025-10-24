@props(['params'])

<x-data.table-cell
    class="bg-table-summary-bg whitespace-nowrap"
    {{ $attributes }}
>
    <span class="font-bold">Клиентов: {{ $params['count'] }}</span>
</x-data.table-cell>
