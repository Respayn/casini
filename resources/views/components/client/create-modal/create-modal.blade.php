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
                    <x-form.input-text placeholder="-" wire:model="inn" x-mask="999999999999" inputmode="numeric"></x-form.input-text>
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
                <x-form.form-label class="self-baseline"
                    tooltip="Поле учитывается при формировании сверки бюджетов, значение может быть как положительное (мы должны), так и отрицательным (нам должны)">
                    Начальная статистика<br>взаиморасчетов
                </x-form.form-label>
                <div>
                    <x-form.input-number wire:model="initialBalance" :allow-negative="true"></x-form.input-number>
                </div>
            </x-form.form-field>
            <x-form.form-field>
                <x-form.form-label class="self-baseline" required
                    tooltip="Сбор с рекламного бюджета, который ДРС удерживает из пополнения кабинета">Расчет сбора 3% в ДРС</x-form.form-label>
                <div>
                    <x-form.select :options="$this->adFeeTypeOptions" wire:model.live="adFeeType"
                        class="w-full"></x-form.select>
                </div>
            </x-form.form-field>
            @if ($adFeeType === \App\Enums\FeeType::NONE->value)
                <x-form.form-field>
                    <x-form.form-label class="self-baseline" required
                        tooltip="С этой даты ДРС не начисляет сбор 3%. Операции до этой даты считаются со сбором">Дата изменения расчета сбора</x-form.form-label>
                    <div class="flex flex-col gap-2">
                        <x-form.date-picker wire:model="adFeeChangedAt" placeholder="дд.мм.гггг" :max="today()->toDateString()" />
                        @error('adFeeChangedAt')
                            <span class="text-warning-red text-[12px]">{{ $message }}</span>
                        @enderror
                    </div>
                </x-form.form-field>
            @endif
            <div
                class="flex justify-between"
                x-data="{
                    get dirty() {
                        const snapshot = $wire.snapshot ?? {};
                        return Object.keys(snapshot).some((key) => String($wire[key] ?? '') !== String(snapshot[key] ?? ''));
                    },
                    get filled() {
                        return String($wire.name ?? '').trim() !== ''
                            && /^\d{10,12}$/.test(String($wire.inn ?? ''))
                            && !! $wire.managerId
                            && !! $wire.adFeeType
                            && ($wire.adFeeType !== '{{ \App\Enums\FeeType::NONE->value }}' || !! $wire.adFeeChangedAt);
                    },
                }"
                x-bind:class="{ 'invisible': ! dirty }"
            >
                <x-button.button variant="primary" type="submit"
                    label="{{ $this->confirmButtonLabel }}" x-bind:disabled="! filled" />
                <x-button.button x-on:click="$dispatch('modal-hide', { name: 'client-modal' })" label="Отменить" />
            </div>
        </x-form.form>
    </x-slot:body>
</x-overlay.modal>