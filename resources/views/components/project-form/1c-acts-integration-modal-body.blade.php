@props([
    'projectIntegration' => null
])

<div
    class="flex h-full flex-col"
    x-data="{
        settings: {
            is_enabled: {{ Js::from($projectIntegration->isEnabled ?? false) }},
            main_contract_number: '{{ $projectIntegration->settings['main_contract_number'] ?? '' }}',
            additional_agreement_number: '{{ $projectIntegration->settings['additional_agreement_number'] ?? '' }}'
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
            <x-form.form-label required>Номер основного договора</x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text x-model="settings.main_contract_number"></x-form.input-text>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label>Номер доп. соглашения</x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text x-model="settings.additional_agreement_number"></x-form.input-text>
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
