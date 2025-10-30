<x-overlay.modal name="group-settings-modal" title="Настроить отчет">
    <x-slot:body>
        <div>
            <div class="flex max-w-[658px] flex-wrap gap-2.5">
                {{-- TODO: изменять отображение кнопок на клиенте, без синхронизации с сервером --}}
                <x-button.button :variant="$grouping->value === 'none' ? 'primary' : null"
                    wire:click="setGrouping('none')" label="Без группировки" />
                <x-button.button :variant="$grouping->value === 'role' ? 'primary' : null"
                    wire:click="setGrouping('role')" label="По ролям" />
                <x-button.button :variant="$grouping->value === 'clients' ? 'primary' : null"
                    wire:click="setGrouping('clients')" label="По клиентам" />
                <x-button.button :variant="$grouping->value === 'project_type' ? 'primary' : null"
                    wire:click="setGrouping('project_type')" label="По отделам" />
                <x-button.button :variant="$grouping->value === 'tools' ? 'primary' : null"
                    wire:click="setGrouping('tools')" label="По инструментам" />
            </div>

            <div class="mt-24 flex justify-between">
                <x-button icon="icons.check" label="Применить" variant="primary" wire:click="$js.apply" />
                <x-button wire:click="$js.cancel" label="Отмена" />
            </div>
        </div>
        </x-slot>
</x-overlay.modal>

@script
<script>
    $wire.$js.apply = () => {
        $wire.initialGrouping = $wire.grouping;
        $dispatch('modal-hide', { name: 'group-settings-modal' })
        $dispatch('group-settings-applied', { grouping: $wire.grouping });
    };

    $wire.$js.cancel = () => {
        $wire.set('grouping', $wire.initialGrouping);
        $dispatch('modal-hide', { name: 'group-settings-modal' })
    };
</script>
@endscript