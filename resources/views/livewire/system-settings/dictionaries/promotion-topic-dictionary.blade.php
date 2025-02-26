<div x-data="{
    resetForm() {
            $wire.selectedPromotionTopicId = 0;
            $wire.form.id = null;
            $wire.form.category = '';
            $wire.form.topic = '';
        },

        selectPromotionTopic(topic) {
            $wire.selectedPromotionTopicId = topic.id;
            $wire.form.id = topic.id;
            $wire.form.category = topic.category;
            $wire.form.topic = topic.topic;
        }
}">
    <x-data.table>
        <x-data.table-columns>
            <x-data.table-column>Категория</x-data.table-column>
            <x-data.table-column>Тематики</x-data.table-column>
            <x-data.table-column></x-data.table-column>
        </x-data.table-columns>

        <x-data.table-rows>
            @foreach ($promotionTopics as $index => $promotionTopic)
                <x-data.table-row wire:key="promotion-topic-{{ $promotionTopic->id }}">
                    <x-data.table-cell>
                        {{ $promotionTopic->category }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        {{ $promotionTopic->topic }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <div class="flex gap-1">
                            <x-overlay.modal-trigger name="promotion-topic-add-modal">
                                <x-button.button
                                    variant="ghost"
                                    icon="icons.edit"
                                    x-on:click="selectPromotionTopic({{ Js::from($promotionTopic) }})"
                                />
                            </x-overlay.modal-trigger>
                            <x-overlay.modal-trigger name="promotion-topic-delete-modal">
                                <x-button.button
                                    variant="ghost"
                                    icon="icons.delete"
                                    x-on:click="$wire.selectedPromotionTopicId = {{ $promotionTopic->id }}"
                                />
                            </x-overlay.modal-trigger>
                        </div>
                    </x-data.table-cell>
                </x-data.table-row>
            @endforeach
        </x-data.table-rows>
    </x-data.table>

    <div class="mt-2">
        <x-overlay.modal-trigger name="promotion-topic-add-modal">
            <x-button.button
                label="Добавить тематику"
                variant="link"
                x-on:click="resetForm()"
            />
        </x-overlay.modal-trigger>
    </div>

    <x-overlay.modal name="promotion-topic-add-modal">
        <x-slot:title>
            <span x-text="$wire.selectedPromotionTopicId === 0 ? 'Добавление тематики' : 'Редактирование тематики'">
            </span>
        </x-slot>

        <x-slot:body>
            <x-form.form class="mb-7">
                <x-form.form-field>
                    <x-form.form-label required>Категория</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model="form.category"></x-form.input-text>
                        @error('form.category')
                            <span class="text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label required>Тематика</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model="form.topic"></x-form.input-text>
                        @error('form.topic')
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
                        <span x-text="$wire.selectedPromotionTopicId === 0 ? 'Создать' : 'Сохранить'"></span>
                    </x-slot>
                </x-button.button>
                <x-button.button
                    label="Отменить"
                    x-on:click="$dispatch('modal-hide', { name: 'promotion-topic-add-modal' })"
                ></x-button.button>
            </div>
        </x-slot:body>
    </x-overlay.modal>

    <x-overlay.modal
        name="promotion-topic-delete-modal"
        title="Удаление тематики"
    >
        <x-slot:body>
            <div>
                Удалить тематику продвижения?
            </div>
            <div class="mt-3 flex justify-between">
                <x-button
                    label="ОК"
                    variant="primary"
                    wire:click="delete"
                />
                <x-button
                    x-on:click="$dispatch('modal-hide', { name: 'promotion-topic-delete-modal' })"
                    label="Отмена"
                />
            </div>
        </x-slot>
    </x-overlay.modal>
</div>
