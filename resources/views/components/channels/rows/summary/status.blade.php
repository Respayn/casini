@props(['params', 'bold' => false])

<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'font-bold' => $bold]) }}>
    <div class="flex flex-col">
        <span>Активно: {{ $params['active'] }}</span>
        <span>Неактивно:  {{ $params['inactive'] }} </span>
    </div>
</x-data.table-cell>
