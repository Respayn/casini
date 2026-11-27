@props(['params'])

@if (!isset($params['sum']) || $params['sum'] === null)
    <x-data.table-cell
        class="bg-[#E9F2FF]"
        {{ $attributes }}
    >
        -
    </x-data.table-cell>
@else
    @php
        $sum = isset($params['sum']) ? \App\Helpers\MoneyDisplay::currency($params['sum']) : 0;
    @endphp

    <x-data.table-cell
        class="bg-[#E9F2FF]"
        {{ $attributes }}
    >
        {{ $sum }}
    </x-data.table-cell>
@endif
