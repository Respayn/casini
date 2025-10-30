<?php

use App\Services\IntegrationService;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    private readonly IntegrationService $integrationService;

    public string $notificationText = '';
    public int $selectedIntegrationId = 0;

    public function boot(IntegrationService $service)
    {
        $this->integrationService = $service;
    }

    #[Computed]
    public function integrations()
    {
        return $this->integrationService->getIntegrations();
    }

    public function updateNotification()
    {
        $this->integrationService->updateNotification(
            $this->pull('selectedIntegrationId'),
            $this->pull('notificationText')
        );
        $this->dispatch('modal-hide', name: 'integration-notification-edit-modal');
    }
}; ?>

<div>
    <x-data.table>
        <x-data.table-columns>
            <x-data.table-column>Интеграция</x-data.table-column>
            <x-data.table-column>Категория</x-data.table-column>
            <x-data.table-column>Пользовательские уведомления</x-data.table-column>
        </x-data.table-columns>

        <x-data.table-rows>
            @foreach ($this->integrations as $integration)
                <x-data.table-row>
                    <x-data.table-cell>
                        {{ $integration->name }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        {{ $integration->category->label() }}
                    </x-data.table-cell>
                    <x-data.table-cell class="text-center">
                        <x-overlay.modal-trigger name="integration-notification-edit-modal">
                            <x-button.button
                                icon="icons.chat"
                                :variant="$integration->notification ? 'primary' : 'ghost'"
                                x-on:click="$wire.selectedIntegrationId = {{ $integration->id }}; $wire.notificationText = `{{ $integration->notification }}`;"
                            >
                            </x-button.button>
                        </x-overlay.modal-trigger>
                    </x-data.table-cell>
                </x-data.table-row>
            @endforeach
        </x-data.table-rows>
    </x-data.table>
    <div class="w-3/4 text-sm font-normal italic text-gray-400">
        Добавление интеграций через программиста
    </div>

    <x-overlay.modal
        name="integration-notification-edit-modal"
        title="Пользовательское уведомление"
    >
        <x-slot:body>
            <x-form.form class="mb-7">
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >
                        Содержание уведомления
                    </x-form.form-label>
                    <div>
                        <x-form.textarea wire:model="notificationText"></x-form.input-text>
                    </div>
                </x-form.form-field>
            </x-form.form>
            <div class="flex justify-between">
                <x-button.button
                    variant="primary"
                    wire:click="updateNotification"
                >
                    <x-slot:label>
                        Сохранить
                    </x-slot>
                </x-button.button>
                <x-button.button
                    label="Отменить"
                    x-on:click="$dispatch('modal-hide', { name: 'integration-notification-edit-modal' })"
                ></x-button.button>
            </div>
        </x-slot>
    </x-overlay.modal>
</div>
