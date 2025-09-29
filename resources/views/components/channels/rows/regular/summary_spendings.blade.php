@props(['params'])

@if ($params['sum'] === null)
    <x-data.table-cell
        class="bg-[#E9F2FF]"
        {{ $attributes }}
    >
        -
    </x-data.table-cell>
@else
    @php
        $sum = isset($params['sum']) ? Number::currency($params['sum'], in: 'RUB', locale: 'ru') : 0;
    @endphp

    <x-data.table-cell
        class="bg-[#E9F2FF]"
        {{ $attributes }}
    >
        {{ $sum }}
    </x-data.table-cell>
@endif
