@props([
    'columns',
])

@foreach ($columns as $column)
    <x-data.table-cell wire:key="refresh-skel-{{ $column->field }}">
        <div
            class="animate-pulse"
            style="height: 14px; width: 100%; border-radius: 0.25rem; background-color: #e5e7eb"
        ></div>
    </x-data.table-cell>
@endforeach
