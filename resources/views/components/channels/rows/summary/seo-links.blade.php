@props(['params'])

@php
    $sum = isset($params['sum']) ? Number::currency($params['sum'], in: 'RUB', locale: 'ru') : 0;
@endphp

<x-data.table-cell class="bg-table-summary-bg" class="bg-[#E9F2FF]" {{ $attributes }}>
    {{ $sum }}
</x-data.table-cell>
