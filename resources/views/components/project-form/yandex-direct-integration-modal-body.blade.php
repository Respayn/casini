<div>
    <x-form.form class="mb-7 lg:min-w-[580px]">
        @if($this->isConnected)
            <div class="bg-green-100 p-4 rounded-lg mb-4">
                <p class="text-green-700">
                    Аккаунт подключен: {{ $yandexDirectSettings->clientLogin }}
                </p>
                <p class="text-sm text-green-600 mt-2">
                    Срок действия токена: {{ $yandexDirectSettings->tokenExpiresAt->format('d.m.Y H:i') }}
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
                    wire:model.live="selectedIntegration.isEnabled"
{{--                    :disabled="!$this->isConnected"--}}
                />
            </div>
        </x-form.form-field>

        @if($this->isConnected)
            <x-form.form-field>
                <x-form.form-label required>Логин</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.input-text
                        type="text"
                        wire:model="selectedIntegration.settings.clientLogin"
                        readonly
                    />
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Модель атрибуции</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.select
                        wire:model="selectedIntegration.settings.attribution_model"
                        :options="[
                            'LSC' => 'Last Significant Click',
                            'LDC' => 'Last Direct Click',
                            'FC' => 'First Click'
                        ]"
                    />
                </div>
            </x-form.form-field>
        @endif
    </x-form.form>
</div>
