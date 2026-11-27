@props(['params', 'bold' => false])

@php
    $hours = isset($params['hours']) ? $params['hours'] : 0;
    $sum = isset($params['sum']) ? \App\Helpers\MoneyDisplay::currency($params['sum']) : 0;
@endphp

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'bg-[#E9F2FF]', 'font-bold' => $bold]) }}>
    {{ $hours }} / {{ $sum }}
</x-data.table-cell>
