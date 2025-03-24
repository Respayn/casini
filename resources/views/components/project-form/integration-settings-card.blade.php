@props([
    'title' => null,
    'description' => null,
    'configuredIntegrations' => [],
    'modalTriggerName'
])

<x-panel.card class="flex-1">
    <x-slot:title>{{ $title }}</x-slot:title>
    <x-slot:content>
        <div class="text-caption-text">
            {{ $description }}
        </div>
        @if (count($configuredIntegrations) > 0)
            <div class="mt-6 flex flex-col gap-2.5">
                @foreach ($configuredIntegrations as $integration)
                    <div class="flex justify-between">
                        <div class="flex items-center gap-3">
                            <x-icons.gear class="text-caption-text" />
                            <span
                                class="text-primary cursor-pointer text-sm hover:underline"
                                wire:click="selectIntegration('{{ $integration->integration->code }}')"
                            >
                                {{ $integration->integration->name }}
                            </span>
                        </div>
                        <x-form.toggle-switch
                            wire:model="integrationSettings.{{ $integration->integration->id }}.isEnabled"
                        />
                    </div>
                @endforeach
            </div>
        @endif
    </x-slot:content>
    <x-slot:footer>
        <x-overlay.modal-trigger :name="$modalTriggerName">
            <x-button.button
                class="w-full"
                variant="primary"
                label="Добавить интеграцию"
                icon="icons.plus"
            />
        </x-overlay.modal-trigger>
    </x-slot:footer>
</x-panel.card>
