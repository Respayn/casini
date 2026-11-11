@props([
    'columns',
])

@foreach ($columns as $column)
    <x-data.table-cell wire:key="refresh-skel-{{ $column->field }}">
        <div class="h-3.5 w-full animate-pulse rounded bg-gray-200"></div>
    </x-data.table-cell>
@endforeach
