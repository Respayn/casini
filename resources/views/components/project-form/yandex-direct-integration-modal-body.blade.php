<div>
    <x-form.form class="mb-7 lg:min-w-[580px]">
        @if($this->integrationSettings[$this->selectedIntegration->integration->id]->isEnabled && !empty($this->selectedIntegration->settings))
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
    </x-form.form>
</div>
