<x-overlay.modal name="group-settings-modal" title="Настроить отчет">
    <x-slot:body>
        <div>
            <x-form.form :is-normalized="true" class="[&_.form-field]:!items-center">
                <x-form.form-field>
                    <x-form.form-label>Сгруппировать отчет</x-form.form-label>
                    <div>
                        <x-form.select
                            wire:model="grouping"
                            :options="[
                                ['label' => 'Без группировки', 'value' => 'none'],
                                ['label' => 'По клиентам', 'value' => 'clients'],
                                ['label' => 'По типу клиенто-проекта', 'value' => 'project_type'],
                            ]"
                        />
                    </div>
                </x-form.form-field>
            </x-form.form>

            <div class="mt-7 flex justify-between">
                <x-button
                    icon="icons.check"
                    label="Применить"
                    variant="primary"
                    wire:click="apply"
                />
                <x-button wire:click="cancel" label="Отмена" />
            </div>
        </div>
        </x-slot>
</x-overlay.modal>
