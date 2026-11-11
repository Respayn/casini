@props([
    'projectIntegration' => null,
    'projectId' => null,
    'platformConfigured' => true,
])

@php
    use App\Models\Integration;
    use App\Models\IntegrationProject;

    $savedSettings = $projectIntegration->settings ?? [];
    $agencyHint = 'Сначала укажите ссылку на таблицу в настройках агентства и положите ключ служебной учётки на сервер';
    $syncIssueMessage = \App\Services\IntegrationSync\IntegrationSyncIssueStatus::messageFromSettings(
        is_array($savedSettings) ? $savedSettings : []
    );

    $syncEnabledAt = $savedSettings['sync_enabled_at'] ?? '';

    if (($projectIntegration->isEnabled ?? false) && $syncEnabledAt === '' && $projectId) {
        $integration = Integration::query()->where('code', 'google_sheets')->first();

        if ($integration) {
            $integrationRecord = IntegrationProject::query()
                ->where('project_id', $projectId)
                ->where('integration_id', $integration->id)
                ->first();

            if ($integrationRecord?->updated_at) {
                $syncEnabledAt = $integrationRecord->updated_at->format('Y-m-d');
            }
        }
    }
@endphp

<div
    class="flex h-full flex-col"
    x-data="{
        platformConfigured: {{ \Illuminate\Support\Js::from($platformConfigured) }},
        syncIssueMessage: {{ \Illuminate\Support\Js::from($syncIssueMessage) }},
        settings: {
            is_enabled: {{ \Illuminate\Support\Js::from($projectIntegration->isEnabled ?? false) }},
            sync_enabled_at: {{ \Illuminate\Support\Js::from($syncEnabledAt) }},
        },

        todayIso() {
            const today = new Date();

            return today.getFullYear()
                + '-' + String(today.getMonth() + 1).padStart(2, '0')
                + '-' + String(today.getDate()).padStart(2, '0');
        },

        init() {
            this.$watch('settings.is_enabled', (enabled) => {
                if (!enabled) {
                    this.settings.sync_enabled_at = '';
                    return;
                }

                if (!this.platformConfigured) {
                    this.settings.is_enabled = false;
                    return;
                }

                if (!this.settings.sync_enabled_at) {
                    this.settings.sync_enabled_at = this.todayIso();
                }
            });
        },

        get syncEnabledLabel() {
            if (!this.settings.sync_enabled_at) {
                return '';
            }

            const [y, m, d] = this.settings.sync_enabled_at.split('-');

            return `включена: ${d}.${m}.${y}`;
        },

        get canSave() {
            if (this.settings.is_enabled && !this.platformConfigured) {
                return false;
            }

            return true;
        },

        save() {
            if (!this.canSave) {
                return;
            }

            const payload = { ...this.settings };

            if (!payload.is_enabled) {
                delete payload.sync_enabled_at;
            } else if (!payload.sync_enabled_at) {
                payload.sync_enabled_at = this.todayIso();
            }

            $wire.setIntegrationSettings({{ $projectIntegration->integration->id }}, payload);
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        handleCancelClick() {
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        get integrationError() {
            return this.syncIssueMessage || null;
        },

        get hasIntegrationError() {
            return this.integrationError !== null && this.integrationError !== '';
        },
    }"
>
    <x-form.form>
        <div
            class="mb-4 break-words rounded-lg border border-[#FF7373] bg-[#FFF5F5] p-4 text-sm text-[#FF7373]"
            x-show="hasIntegrationError"
            x-text="integrationError"
            x-cloak
        ></div>

        <x-form.form-field class="w-[603px]">
            <x-form.form-label>Синхронизация</x-form.form-label>
            <div class="flex w-[305px] items-center gap-4">
                @if ($platformConfigured)
                    <div :class="{ 'yd-direct-error-toggle': hasIntegrationError && settings.is_enabled }">
                        <x-form.toggle-switch x-model="settings.is_enabled"></x-form.toggle-switch>
                    </div>
                @else
                    <x-overlay.tooltip panel-max-width="20rem">
                        <x-slot:trigger>
                            <span class="inline-block">
                                <x-form.toggle-switch
                                    x-model="settings.is_enabled"
                                    disabled
                                ></x-form.toggle-switch>
                            </span>
                        </x-slot:trigger>
                        {{ $agencyHint }}
                    </x-overlay.tooltip>
                @endif
                <span
                    class="text-secondary-text text-sm"
                    x-show="settings.is_enabled"
                    x-text="syncEnabledLabel"
                    x-cloak
                ></span>
            </div>
        </x-form.form-field>
    </x-form.form>

    <x-project-form.integration-modal-footer class="mt-auto border-0 pt-0" />
</div>
