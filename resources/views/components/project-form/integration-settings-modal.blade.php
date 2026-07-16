@props([
    'projectIntegration' => null,
    'projectId' => null,
    'platformConfigured' => true,
    'bodyRevision' => 0,
])

@php
    $formattedIntegrationCode = Str::kebab(Str::camel($projectIntegration?->integration->code));
    $integrationCode = $projectIntegration?->integration->code;
    $isConfigured = match ($integrationCode) {
        'callibri' => filled($projectIntegration->settings['token'] ?? null)
            && filled($projectIntegration->settings['site_id'] ?? null),
        'yandex_search_api' => filled($projectIntegration->settings['regions'] ?? null)
            && count($projectIntegration->settings['regions'] ?? []) > 0,
        'yandex_direct' => filled($projectIntegration->settings['oauth_token'] ?? $projectIntegration->settings['encryptedOauthToken'] ?? null)
            || filled($projectIntegration->settings['client_login'] ?? $projectIntegration->settings['clientLogin'] ?? null)
            || ($projectIntegration->isEnabled ?? false),
        default => false,
    };

    $modalBodyKey = $projectIntegration
        ? $integrationCode.'-'.$projectIntegration->integration->id.'-'.md5(json_encode([
            'settings' => $projectIntegration->settings ?? [],
            'enabled' => $projectIntegration->isEnabled ?? false,
        ])).'-'.$bodyRevision
        : 'empty';

    $modalSidebarKey = $projectIntegration
        ? $integrationCode.'-'.$bodyRevision
        : 'empty';
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
            <div wire:key="integration-modal-body-{{ $modalBodyKey }}">
                <x-dynamic-component
                    component="project-form.{{ $formattedIntegrationCode }}-integration-modal-body"
                    :project-integration="$projectIntegration"
                    :project-id="$projectId"
                    :platform-configured="$platformConfigured"
                />
            </div>
        </x-slot:body>

        <x-slot:sidebar>
            <div wire:key="integration-modal-sidebar-{{ $modalSidebarKey }}">
                <x-dynamic-component component="project-form.{{ $formattedIntegrationCode }}-integration-modal-sidebar" />
            </div>
        </x-slot:sidebar>
    @endif
</x-overlay.modal>
