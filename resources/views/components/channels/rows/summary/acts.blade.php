@props(['params', 'bold' => false])

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'font-bold' => $bold]) }}>
    {{ \App\Helpers\MoneyDisplay::currency($params) }}
</x-data.table-cell>
