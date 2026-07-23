@props([
    'projectIntegration' => null,
    'projectId' => null,
])

@php
    $formattedIntegrationCode = Str::kebab(Str::camel($projectIntegration?->integration->code));
    $isConfigured = match ($projectIntegration?->integration->code) {
        'callibri' => filled($projectIntegration->settings['email'] ?? null)
            && filled($projectIntegration->settings['token'] ?? null)
            && filled($projectIntegration->settings['site_id'] ?? null),
        default => false,
    };
@endphp

<x-overlay.modal
    name="integration-settings-modal"
    title="{{ $projectIntegration?->integration->name }}"
>
    @if ($isConfigured)
        <x-slot:titleActions>
            <x-button.button
                label="Удалить интеграцию"
                x-on:click="$wire.removeIntegration({{ $projectIntegration->integration->id }}); $dispatch('modal-hide', { name: 'integration-settings-modal' })"
            />
        </x-slot:titleActions>
    @endif

    @if ($projectIntegration)
        <x-slot:body>
            <x-dynamic-component
                component="project-form.{{ $formattedIntegrationCode }}-integration-modal-body"
                :project-integration="$projectIntegration"
                :project-id="$projectId"
            />
        </x-slot:body>

        <x-slot:sidebar>
            <x-dynamic-component component="project-form.{{ $formattedIntegrationCode }}-integration-modal-sidebar" />
        </x-slot:sidebar>
    @endif
</x-overlay.modal>
