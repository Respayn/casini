<x-overlay.modal name="client-modal" title="{{ $this->modalTitle }}">
    <x-slot:body>
        <x-form.form :is-normalized="true" wire:submit.prevent="saveClient" class="min-w-[723px]">
            <x-form.form-field>
                <x-form.form-label class="self-baseline" required
                    tooltip="Заполните название клиента, так клиент будет отображаться во всех продуктах. Обязательное поле для заполнени">Клиент</x-form.form-label>
                <div>
                    <x-form.input-text wire:model="name"></x-form.input-text>
                </div>
            </x-form.form-field>
            <x-form.form-field>
                <x-form.form-label class="self-baseline" required
                    tooltip="С помощью ИНН мы можем автоматически определять операции по клиенту">ИНН</x-form.form-label>
                <div>
                    <x-form.input-text placeholder="-" wire:model="inn"></x-form.input-text>
                </div>
            </x-form.form-field>
            <x-form.form-field>
                <x-form.form-label class="self-baseline" required
                    tooltip="Выберите менеджера, все клиенто-проекты этого клиента будут привязаны к этому менеджеру">Менеджер</x-form.form-label>
                <div>
                    <x-form.select placeholder="Не выбрано" :options='$this->managerOptions' wire:model="managerId"
                        class="w-full"></x-form.select>
                </div>
            </x-form.form-field>
            <x-form.form-field>
                <x-form.form-label class="self-baseline" required
                    tooltip="Поле учитывается при формировании сверки бюджетов, значение может быть как положительное (мы должны), так и отрицательным (нам должны)">
                    Начальная статистика взаиморасчетов
                </x-form.form-label>
                <div>
                    <x-form.input-text type="number" wire:model="initialBalance"></x-form.input-text>
                </div>
            </x-form.form-field>
            <div class="flex justify-between">
                <x-button.button icon="icons.check" variant="primary" type="submit"
                    label="{{ $this->confirmButtonLabel }}" />
                <x-button.button x-on:click="$dispatch('modal-hide', { name: 'client-modal' })" label="Отменить" />
            </div>
        </x-form.form>
    </x-slot:body>
</x-overlay.modal>