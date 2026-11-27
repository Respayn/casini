@props(['params', 'bold' => false])

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'bg-[#E9F2FF]', 'font-bold' => $bold]) }}>
    {{ $params === null || ! isset($params['sum']) ? '-' : \App\Helpers\MoneyDisplay::currency($params['sum']) }}
</x-data.table-cell>
