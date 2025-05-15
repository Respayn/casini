<div
    class="flex h-full flex-col"
    x-data="{
        settings: {
            is_enabled: {{ Js::from($projectIntegration->isEnabled ?? false) }},
            clientLogin: '{{ $projectIntegration->settings['clientLogin'] ?? null }}',
            accountId: '{{ $projectIntegration->settings['accountId'] ?? null }}',
            encryptedOauthToken: '{{ $projectIntegration->settings['encryptedOauthToken'] ?? null }}',
            encryptedRefreshToken: '{{ $projectIntegration->settings['encryptedRefreshToken'] ?? null }}',
            tokenExpiresAt: '{{ $projectIntegration->settings['tokenExpiresAt'] ?? null }}',
            login: '{{ $projectIntegration->settings['login'] ?? '' }}',
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
    <x-form.form class="mb-7 lg:min-w-[580px]">
        @if(!empty($this->integrationSettings[$this->selectedIntegration->integration->id])
            && $this->integrationSettings[$this->selectedIntegration->integration->id]->isEnabled
            && !empty($this->selectedIntegration->settings))
            <div class="bg-green-100 p-4 rounded-lg mb-4">
                <p class="text-green-700">
                    Аккаунт подключен: {{ $this->selectedIntegration->settings['clientLogin'] }}
                </p>
                <p class="text-sm text-green-600 mt-2">
                    Токен действителен до: {{ \Carbon\Carbon::parse($this->selectedIntegration->settings['tokenExpiresAt'])->format('d.m.Y H:i') }}
                </p>
            </div>
        @endif

        <x-form.form-field>
            <x-form.form-label
                class="self-baseline"
                tooltip="Ползунок синхронизации аккаунта Яндекс.Директ"
            >
                Синхронизация
            </x-form.form-label>
            <div>
                <x-form.toggle-switch
                    wire:model.live="integrationSettings.{{ $this->selectedIntegration->integration->id }}.isEnabled"
{{--                    :disabled="!$this->isConnected"--}}
                />
            </div>
        </x-form.form-field>
        <x-form.form-field>
            <x-form.form-label required>Логин</x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text x-model="settings.login"></x-form.input-text>
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
