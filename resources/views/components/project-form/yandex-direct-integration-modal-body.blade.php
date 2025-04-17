@props([
    'projectIntegration' => null
])

<div
    class="flex h-full flex-col"
    x-data="{
        settings: {
            is_enabled: {{ Js::from($projectIntegration->isEnabled ?? false) }},
            login: '{{ $projectIntegration->settings['login'] ?? '' }}',
            access_token: '{{ $projectIntegration->settings['access_token'] ?? '' }}',
            refresh_token: '{{ $projectIntegration->settings['refresh_token'] ?? '' }}'
        },
        errors: {
            login: ''
        },
    
        save() {
            this.errors.login = '';
            
            if (!this.settings.login.trim()) {
                this.errors.login = 'Поле обязательно для заполнения';
                return;
            }

            $wire.setIntegrationSettings({{ $projectIntegration->integration->id }}, this.settings);
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        handleCancelClick() {
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        
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
            <x-form.form-label required>Логин</x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text x-model="settings.login"></x-form.input-text>
                <span x-show="errors.login" x-text="errors.login" class="text-warning-red text-[12px]"></span>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label>Токен</x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text x-model="settings.access_token"></x-form.input-text>
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
