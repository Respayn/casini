@props([
    'name' => 'column-settings-modal',
    'title' => 'Настроить столбцы',
    'columns'
])

<x-overlay.modal :name="$name" :title="$title">
    <x-slot:body>
        <div class="flex flex-col gap-2.5" x-data x-sort="$wire.sortColumn($item, $position)">
            @foreach ($columns as $index => $column)
                <div class="flex items-center gap-2.5" wire:key="column.{{ $column['key'] }}"
                    x-sort:item="'{{ $column['key'] }}'">
                    <x-icons.burger class="text-secondary-text cursor-pointer" x-sort:handle />
                    <x-form.checkbox :checked="$column['is_visible']" wire:click="toggleColumnVisibility('{{ $column['key'] }}')" />
                    <label>{{ $column['label'] }}</label>
                </div>
            @endforeach
        </div>

        <div class="mt-3 flex justify-between">
            <x-button icon="icons.check" label="Применить" variant="primary"
                x-on:click="$dispatch('modal-hide', { name: '{{ $name }}' }); $wire.applyColumnSettings()" />
            <x-button
                x-on:click="$dispatch('modal-hide', { name: '{{ $name }}' }); $wire.revertColumnSettings()"
                label="Отмена" />
        </div>
    </x-slot>
</x-overlay.modal>