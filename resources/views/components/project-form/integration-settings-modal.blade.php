@props([
    'projectIntegration' => null,
    'projectId' => null,
    'platformConfigured' => true,
    'bodyRevision' => 0,
    'canEdit' => true,
    'projectType' => null,
])

@php
    use App\Support\IntegrationLogo;

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
        'yandex_metrika' => filled($projectIntegration->settings['oauth_token'] ?? $projectIntegration->settings['encryptedOauthToken'] ?? null)
            || filled($projectIntegration->settings['counter_id'] ?? $projectIntegration->settings['counterId'] ?? null)
            || ($projectIntegration->isEnabled ?? false),
        'google_sheets' => ($projectIntegration->isEnabled ?? false)
            || filled($projectIntegration->settings['sync_enabled_at'] ?? null),
        '1c_acts' => filled($projectIntegration->settings['main_contract_number'] ?? null)
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
        ? $integrationCode.'-'.$bodyRevision.'-'.(string) $projectType
        : 'empty';

    $logoComponent = $integrationCode
        ? 'icons.logo.'.IntegrationLogo::component((string) $integrationCode)
        : null;

    $sidebarComponent = 'project-form.'.$formattedIntegrationCode.'-integration-modal-sidebar';
@endphp

<x-overlay.modal
    name="integration-settings-modal"
    title="{{ $projectIntegration?->integration->name }}"
    :icon="$logoComponent"
    :collapsible-sidebar="true"
>
    @if ($isConfigured && $canEdit)
        <x-slot:titleActions>
            <x-button.button
                icon="icons.delete"
                label="Удалить интеграцию"
                x-on:click="$dispatch('modal-show', { name: 'integration-delete-confirm' })"
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
                    :can-edit="$canEdit"
                />
            </div>
        </x-slot:body>

        <x-slot:sidebar>
            <div wire:key="integration-modal-sidebar-{{ $modalSidebarKey }}">
                @if ($integrationCode === 'yandex_metrika')
                    <x-dynamic-component
                        :component="$sidebarComponent"
                        :project-type="$projectType"
                    />
                @else
                    <x-dynamic-component :component="$sidebarComponent" />
                @endif
            </div>
        </x-slot:sidebar>
    @endif
</x-overlay.modal>

@if ($isConfigured && $canEdit && $projectIntegration)
    <x-overlay.modal
        name="integration-delete-confirm"
        title="Вы уверены?"
        :stacked="true"
    >
        <x-slot:body>
            <p class="text-primary-text">Интеграция будет удалена.</p>
            <div class="mt-6 flex justify-between gap-4">
                <x-button.button
                    icon="icons.delete"
                    label="Удалить"
                    variant="primary"
                    x-on:click="
                        $wire.removeIntegration({{ $projectIntegration->integration->id }});
                        $dispatch('modal-hide', { name: 'integration-delete-confirm' });
                        $dispatch('modal-hide', { name: 'integration-settings-modal' });
                    "
                />
                <x-button.button
                    label="Отменить"
                    x-on:click="$dispatch('modal-hide', { name: 'integration-delete-confirm' })"
                />
            </div>
        </x-slot:body>
    </x-overlay.modal>
@endif

@if ($projectIntegration)
    <x-overlay.modal
        name="integration-instruction-modal"
        title="Инструкция"
        :stacked="true"
    >
        <x-slot:body>
            {{-- Без пересборки Tailwind: прячем повтор заголовка и min-width сайдбаров Директа/Метрики. --}}
            <style>
                .integration-instruction-body .text-2xl {
                    display: none !important;
                }

                .integration-instruction-body [class*='min-w-'] {
                    min-width: 0 !important;
                }
            </style>
            <div
                wire:key="integration-instruction-body-{{ $modalSidebarKey }}"
                class="integration-instruction-body"
            >
                @if ($integrationCode === 'yandex_metrika')
                    <x-dynamic-component
                        :component="$sidebarComponent"
                        :project-type="$projectType"
                    />
                @else
                    <x-dynamic-component :component="$sidebarComponent" />
                @endif
            </div>
        </x-slot:body>
    </x-overlay.modal>
@endif
