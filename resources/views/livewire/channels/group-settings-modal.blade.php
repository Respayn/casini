<x-overlay.modal name="group-settings-modal" title="Настроить отчет">
    <x-slot:body>
        <div>
            <div class="flex max-w-[658px] flex-wrap gap-2.5">
                {{-- TODO: изменять отображение кнопок на клиенте, без синхронизации с сервером --}}
                <x-button.button :variant="$grouping->value === 'none' ? 'primary' : null"
                    wire:click="setGrouping('none')" label="Без группировки" />
                {{-- TODO: Динамически по ролям --}}
                {{-- <x-button.button label='По роли "SEO-специалист"' />
                <x-button.button label='По роли "PPC-специалист"' />
                <x-button.button label='По роли "Менеджер"' /> --}}
                {{-- END TODO --}}
                <x-button.button :variant="$grouping->value === 'clients' ? 'primary' : null"
                    wire:click="setGrouping('clients')" label="По клиентам" />
                <x-button.button :variant="$grouping->value === 'project_type' ? 'primary' : null"
                    wire:click="setGrouping('project_type')" label="По отделам" />
                {{-- <x-button.button label="По инструментам" /> --}}
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
    $js('apply', () => {
        $wire.initialGrouping = $wire.grouping;
        $dispatch('modal-hide', { name: 'group-settings-modal' })
        $dispatch('group-settings-applied', { grouping: $wire.grouping });
    })

    $js('cancel', () => {
        $wire.set('grouping', $wire.initialGrouping);
        $dispatch('modal-hide', { name: 'group-settings-modal' })
    })
</script>
@endscript