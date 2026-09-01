@props(['params', 'bold' => false])

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'font-bold' => $bold]) }}>

    <span>Итого: {{ $params['count'] }}</span>
</x-data.table-cell>
