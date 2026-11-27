@props(['params'])

@if ($params === null)
    <x-data.table-cell
        class="bg-[#E9F2FF]"
        {{ $attributes }}
    >
        -
    </x-data.table-cell>
@else
    @php
        $hours = isset($params['hours']) ? $params['hours'] : 0;
        $sum = isset($params['sum']) ? \App\Helpers\MoneyDisplay::currency($params['sum']) : 0;
    @endphp

    <x-data.table-cell
        class="bg-[#E9F2FF]"
        {{ $attributes }}
    >
        {{ $hours }} / {{ $sum }}
    </x-data.table-cell>
@endif
