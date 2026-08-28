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
        $hours = $params['hours'] ?? 0;
        $sum = Number::currency($params['sum'] ?? 0, in: 'RUB', locale: 'ru');
    @endphp

    <x-data.table-cell
        class="bg-[#E9F2FF]"
        {{ $attributes }}
    >
        {{ $hours }} / {{ $sum }}
    </x-data.table-cell>
@endif
