<div x-data="rateDictionary">
    <x-data.table>
        <x-data.table-columns>
            <x-data.table-column>Название</x-data.table-column>
            <x-data.table-column>Ставка (₽ / час)</x-data.table-column>
            <x-data.table-column>Применение ставки с:</x-data.table-column>
            <x-data.table-column>До (включительно):</x-data.table-column>
            <x-data.table-column>Действия</x-data.table-column>
            <x-data.table-column>Удаление</x-data.table-column>
        </x-data.table-columns>

        <x-data.table-rows>
            @foreach ($this->rates as $index => $rate)
                @php
                    $rowspan = $rate->values->count();
                    $rateHistory = $rate->values->slice(1, $rowspan);
                @endphp
                <x-data.table-row
                    x-data="{
                        rate: {{ json_encode($rate) }},
                        toggleHistory: function(rateId) {
                            $wire.historyExpandedStates[rateId] = !$wire.historyExpandedStates[rateId];
                        }
                    }"
                    wire:key="rate-{{ $rate->id }}"
                >
                    <x-data.table-cell
                        x-bind:rowspan="$wire.historyExpandedStates[{{ $rate->id }}] ? {{ $rowspan }} : 1"
                    >
                        {{ $rate->name }}
                    </x-data.table-cell>
                    <x-data.table-cell
                        x-text="$wire.historyExpandedStates[{{ $rate->id }}] ? '{{ $rate->values->first()->value }}' : '{{ $rate->actualValue ?? '-' }}'"
                    />
                    <x-data.table-cell
                        x-text="$wire.historyExpandedStates[{{ $rate->id }}] ? '{{ $rate->values->first()->startDate->format('d.m.Y') ?? '-' }}' : '{{ $rate->actualStartDate?->format('d.m.Y') ?? '-' }}'"
                    />
                    <x-data.table-cell
                        x-text="$wire.historyExpandedStates[{{ $rate->id }}] ? '{{ $rate->values->first()->endDate?->format('d.m.Y') ?? '-' }}' : '{{ $rate->actualEndDate?->format('d.m.Y') ?? '-' }}'"
                    />
                    <x-data.table-cell
                        x-bind:rowspan="$wire.historyExpandedStates[{{ $rate->id }}] ? {{ $rowspan }} : 1"
                    >
                        <div class="flex gap-1">
                            <x-button.button
                                x-on:click="$wire.historyExpandedStates[{{ $rate->id }}] = !$wire.historyExpandedStates[{{ $rate->id }}]"
                                icon="icons.history"
                            />
                            <x-overlay.modal-trigger name="rate-dictionary-add-modal">
                                <x-button.button
                                    x-on:click="selectRate(rate)"
                                    icon="icons.edit"
                                />
                            </x-overlay.modal-trigger>
                        </div>
                    </x-data.table-cell>
                    <x-data.table-cell
                        class="text-center"
                        x-bind:rowspan="$wire.historyExpandedStates[{{ $rate->id }}] ? {{ $rowspan }} : 1"
                    >
                        <x-overlay.modal-trigger name="rate-dictionary-delete-modal">
                            <x-button.button
                                x-on:click="selectRate(rate)"
                                icon="icons.delete"
                            />
                        </x-overlay.modal-trigger>
                    </x-data.table-cell>
                </x-data.table-row>
                @foreach ($rateHistory as $rateValue)
                    <x-data.table-row
                        x-show="$wire.historyExpandedStates[{{ $rate->id }}]"
                        wire:key="rate-value-{{ $rateValue->id }}"
                    >
                        <x-data.table-cell>
                            {{ $rateValue->value }}
                        </x-data.table-cell>
                        <x-data.table-cell>
                            {{ $rateValue->startDate->format('d.m.Y') }}
                        </x-data.table-cell>
                        <x-data.table-cell>
                            {{ $rateValue->endDate?->format('d.m.Y') ?? '-' }}
                        </x-data.table-cell>
                    </x-data.table-row>
                @endforeach
            @endforeach
        </x-data.table-rows>
    </x-data.table>

    <div class="mt-2">
        <x-overlay.modal-trigger name="rate-dictionary-add-modal">
            <x-button.button
                label="Добавить ставку"
                variant="link"
                x-on:click="resetForm"
            />
        </x-overlay.modal-trigger>
    </div>

    <x-overlay.modal name="rate-dictionary-add-modal">
        <x-slot:title>
            <span x-text="$wire.selectedRateId === 0 ? 'Добавление ставки' : 'Редактирование ставки'"></span>
        </x-slot>

        <x-slot:body>
            <x-form.form class="mb-7">
                <x-form.form-field>
                    <x-form.form-label required>Название</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model="form.name"></x-form.input-text>
                        @error('form.name')
                            <span class="text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label required>Ставка (₽ / час)</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model="form.value"></x-form.input-text>
                        @error('form.value')
                            <span class="text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label required>Применение ставки с:</x-form.form-label>
                    <div>
                        <x-form.date-picker wire:model="form.startDate"></x-form.date-picker>
                        @error('form.startDate')
                            <span class="text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </x-form.form-field>
            </x-form.form>
            <div class="flex justify-between">
                <x-button.button
                    variant="primary"
                    wire:click="store"
                >
                    <x-slot:label>
                        <span x-text="$wire.selectedRateId === 0 ? 'Создать' : 'Сохранить'"></span>
                    </x-slot>
                </x-button.button>
                <x-button.button
                    label="Отменить"
                    x-on:click="$dispatch('modal-hide', { name: 'rate-dictionary-add-modal' })"
                ></x-button.button>
            </div>
        </x-slot:body>
    </x-overlay.modal>

    <x-overlay.modal
        name="rate-dictionary-delete-modal"
        title="Удаление ставки"
    >
        <x-slot:body>
            <div>
                Удалить ставку?
            </div>
            <div class="mt-3 flex justify-between">
                <x-button
                    label="ОК"
                    variant="primary"
                    wire:click="delete"
                />
                <x-button
                    x-on:click="$dispatch('modal-hide', { name: 'rate-dictionary-delete-modal' })"
                    label="Отмена"
                />
            </div>
        </x-slot>
    </x-overlay.modal>
</div>

@script
    <script>
        Alpine.data('rateDictionary', () => {
            return {
                resetForm() {
                    $wire.form.name = '';
                    $wire.form.value = '';
                    $wire.form.startDate = '';
                    $wire.selectedRateId = 0;
                },
                selectRate(rate) {
                    $wire.form.name = rate.name;
                    $wire.form.value = rate.values?.[0]?.value;
                    $wire.form.startDate = rate.values?.[0]?.startDate ?
                        new Date(rate.values?.[0]?.startDate).toISOString().split('T')[0] :
                        null;
                    $wire.selectedRateId = rate.id;
                }
            }
        });
    </script>
@endscript
