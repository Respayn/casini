<div>
    <x-data.table>
        <x-data.table-columns>
            <x-data.table-column>Регион продвижения</x-data.table-column>
            <x-data.table-column></x-data.table-column>
        </x-data.table-columns>

        <x-data.table-rows>
            @foreach ($promotionRegions as $index => $promotionRegion)
                <x-data.table-row
                    x-data="{ isEditing: false }"
                    wire:key="promotion-region-{{ $promotionRegion->id }}"
                >
                    <x-data.table-cell>
                        <div x-show="!isEditing">
                            {{ $promotionRegion->name }}
                        </div>
                        <div x-show="isEditing">
                            <x-form.input-text wire:model="promotionRegions.{{ $index }}.name" />
                        </div>
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <div class="flex gap-1">
                            <x-button.button
                                variant="ghost"
                                icon="icons.edit"
                                x-show="!isEditing"
                                x-on:click="isEditing = true"
                            />
                            <x-overlay.modal-trigger
                                name="promotion-region-delete-modal"
                                x-show="!isEditing"
                            >
                                <x-button.button
                                    variant="ghost"
                                    icon="icons.delete"
                                    x-on:click="$wire.selectedPromotionRegionId = {{ $promotionRegion->id }}"
                                />
                            </x-overlay.modal-trigger>
                            <x-button.button
                                variant="ghost"
                                icon="icons.check"
                                x-on:click="isEditing = false; $wire.update({{ $promotionRegion->id }})"
                                x-show="isEditing"
                            />
                            <x-button.button
                                variant="ghost"
                                icon="icons.close"
                                x-on:click="isEditing = false; $wire.promotionRegions[{{ $index }}].name = '{{ $promotionRegion->name }}'"
                                x-show="isEditing"
                            />
                        </div>
                    </x-data.table-cell>
                </x-data.table-row>
            @endforeach
        </x-data.table-rows>
    </x-data.table>

    <div class="mt-2">
        <x-overlay.modal-trigger name="promotion-region-add-modal">
            <x-button.button
                label="Добавить регион"
                variant="link"
            />
        </x-overlay.modal-trigger>
    </div>

    <x-overlay.modal
        name="promotion-region-add-modal"
        title="Добавление региона"
    >
        <x-slot:body>
            <x-form.form class="mb-7">
                <x-form.form-field>
                    <x-form.form-label required>Название</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model="createForm.name"></x-form.input-text>
                        @error('createForm.name')
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
                        Создать
                    </x-slot>
                </x-button.button>
                <x-button.button
                    label="Отменить"
                    x-on:click="$dispatch('modal-hide', { name: 'promotion-region-add-modal' })"
                ></x-button.button>
            </div>
        </x-slot:body>
    </x-overlay.modal>

    <x-overlay.modal
        name="promotion-region-delete-modal"
        title="Удаление региона"
    >
        <x-slot:body>
            <div>
                Удалить регион продвижения?
            </div>
            <div class="mt-3 flex justify-between">
                <x-button
                    label="ОК"
                    variant="primary"
                    wire:click="delete"
                />
                <x-button
                    x-on:click="$dispatch('modal-hide', { name: 'promotion-region-delete-modal' })"
                    label="Отмена"
                />
            </div>
        </x-slot>
    </x-overlay.modal>
</div>
