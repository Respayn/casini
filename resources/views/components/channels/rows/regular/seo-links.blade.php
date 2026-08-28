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
        $sum = Number::currency($params['sum'] ?? 0, in: 'RUB', locale: 'ru');
    @endphp

    <x-data.table-cell
        class="bg-[#E9F2FF]"
        {{ $attributes }}
    >
        {{ $sum }}
    </x-data.table-cell>
@endif
