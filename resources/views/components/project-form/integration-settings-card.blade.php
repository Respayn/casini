@props([
    'title' => null,
    'description' => null,
    'configuredIntegrations' => [],
    'modalTriggerName',
    'canEdit' => true,
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
                    <div class="flex min-h-10 items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            @if ($canEdit)
                                <button
                                    type="button"
                                    class="text-caption-text hover:text-primary inline-flex h-[18px] w-[18px] shrink-0 cursor-pointer items-center justify-center"
                                    wire:click="selectIntegration('{{ $integration->integration->code }}')"
                                    title="Настройки интеграции"
                                >
                                    <x-icons.gear class="h-[18px] w-[18px] shrink-0" />
                                </button>
                                <span
                                    class="text-primary cursor-pointer text-sm hover:underline"
                                    wire:click="selectIntegration('{{ $integration->integration->code }}')"
                                >
                                    {{ $integration->integration->name }}
                                </span>
                            @else
                                <span class="text-caption-text inline-flex h-[18px] w-[18px] shrink-0 items-center justify-center">
                                    <x-icons.gear class="h-[18px] w-[18px] shrink-0" />
                                </span>
                                <x-permissions.field-guard :enabled="false" :fill="false">
                                    <span class="text-primary text-sm">
                                        {{ $integration->integration->name }}
                                    </span>
                                </x-permissions.field-guard>
                            @endif
                        </div>
                        <x-permissions.field-guard :enabled="$canEdit" :fill="false">
                            <x-form.toggle-switch
                                wire:model.live="integrationSettings.{{ $integration->integration->id }}.isEnabled"
                                :disabled="! $canEdit"
                            />
                        </x-permissions.field-guard>
                    </div>
                @endforeach
            </div>
        @endif
    </x-slot:content>
    <x-slot:footer>
        @if ($canEdit)
            <x-overlay.modal-trigger :name="$modalTriggerName">
                <x-button.button
                    class="w-full"
                    variant="primary"
                    label="Добавить интеграцию"
                    icon="icons.plus"
                />
            </x-overlay.modal-trigger>
        @else
            <x-permissions.field-guard :enabled="false">
                <x-button.button
                    class="w-full"
                    variant="primary"
                    label="Добавить интеграцию"
                    icon="icons.plus"
                    disabled
                />
            </x-permissions.field-guard>
        @endif
    </x-slot:footer>
</x-panel.card>
