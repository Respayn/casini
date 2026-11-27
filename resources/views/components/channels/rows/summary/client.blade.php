@props(['params', 'bold' => false])

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'whitespace-nowrap', 'font-bold' => $bold]) }}>

    <span>{{ $params['count'] ?? '-' }}</span>
</x-data.table-cell>
