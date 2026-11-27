@props(['params'])

<x-data.table-cell {{ $attributes }}>
    {{ \App\Helpers\MoneyDisplay::currency($params) }}
</x-data.table-cell>
