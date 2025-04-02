@props([
    'projectIntegration' => null,
])

<div
    class="flex h-full flex-col"
    x-data="{
        settings: {
            is_enabled: {{ Js::from($projectIntegration->isEnabled ?? false) }},
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
