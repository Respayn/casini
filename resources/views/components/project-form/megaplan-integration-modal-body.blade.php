@props([
    'projectIntegration' => null
])

<div
    class="flex h-full flex-col"
    x-data="{
        settings: {
            is_enabled: {{ Js::from($projectIntegration->isEnabled ?? false) }},
            ticket_number: '{{ $projectIntegration->settings['ticket_number'] ?? '' }}',
            search_string_suffix: '{{ $projectIntegration->settings['search_string_suffix'] ?? '' }}',
            parse_from_comments: {{ Js::from($projectIntegration->settings['parse_from_comments'] ?? false) }}
        },
    
        save() {
            $wire.setIntegrationSettings({{ $projectIntegration->integration->id }}, this.settings);
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        handleCancelClick() {
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        }
    }"
>
    <x-form.form>
        <x-form.form-field class="w-[603px]">
            <x-form.form-label>Синхронизация</x-form.form-label>
            <div>
                <x-form.toggle-switch x-model="settings.is_enabled"></x-form.toggle-switch>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label required>№ корневого тикета</x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text x-model="settings.ticket_number"></x-form.input-text>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label>Дополнение к строке поиска</x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text x-model="settings.search_string_suffix"></x-form.input-text>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label>Парсить работы из комментариев</x-form.form-label>
            <div>
                <x-form.toggle-switch x-model="settings.parse_from_comments"></x-form.toggle-switch>
            </div>
        </x-form.form-field>
    </x-form.form>
    <div class="mt-auto flex justify-between">
        <x-button.button
            variant="primary"
            label="Сохранить изменения"
            x-on:click="save"
        />
        <x-button.button
            label="Отменить"
            x-on:click="handleCancelClick"
        />
    </div>
</div>
