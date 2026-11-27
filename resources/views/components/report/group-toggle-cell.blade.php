@props([
    'label',
    'groupIndex',
])

{{-- Ячейка строки «Итого»: название группы и стрелка свернуть/развернуть. --}}
<x-data.table-cell {{ $attributes->class(['bg-table-summary-bg', 'whitespace-nowrap']) }}>
    <div
        class="flex cursor-pointer items-center gap-2"
        x-on:click="expandedGroups['group-{{ $groupIndex }}'] = !expandedGroups['group-{{ $groupIndex }}']"
    >
        <span class="font-bold">{{ $label }}</span>
        <x-icons.arrow
            class="transition-transform duration-200"
            x-bind:class="{ 'rotate-180': expandedGroups['group-{{ $groupIndex }}'] }"
        />
    </div>
</x-data.table-cell>
