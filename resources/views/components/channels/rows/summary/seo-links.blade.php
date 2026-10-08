@props(['params', 'bold' => false])

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'bg-[#E9F2FF]', 'font-bold' => $bold]) }}>
    {{ $params === null || ! isset($params['sum']) ? '-' : Number::currency($params['sum'], in: 'RUB', locale: 'ru') }}
</x-data.table-cell>
