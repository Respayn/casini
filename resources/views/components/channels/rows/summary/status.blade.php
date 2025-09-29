@props(['params'])

<x-data.table-cell class="bg-table-summary-bg" {{ $attributes }}>
    <div class="flex flex-col font-bold">
        <span>Активно: {{ $params['active'] }}</span>
        <span>Неактивно:  {{ $params['inactive'] }} </span>
    </div>
</x-data.table-cell>
