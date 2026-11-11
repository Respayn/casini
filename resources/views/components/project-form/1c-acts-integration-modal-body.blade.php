@props([
    'canEdit' => true,
    'projectIntegration' => null,
    'projectId' => null,
    'platformConfigured' => true,
])

@php
    $savedSettings = $projectIntegration->settings ?? [];
    $syncIssueMessage = \App\Services\IntegrationSync\IntegrationSyncIssueStatus::messageFromSettings(
        is_array($savedSettings) ? $savedSettings : []
    );

    $settings = [
        'is_enabled' => $projectIntegration->isEnabled ?? false,
        'sync_enabled_at' => $savedSettings['sync_enabled_at'] ?? '',
        'main_contract_number' => $savedSettings['main_contract_number'] ?? '',
        'additional_agreement_number' => $savedSettings['additional_agreement_number'] ?? '',
    ];
@endphp

<div
    class="flex h-full flex-col"
    x-data="{
        canEdit: {{ Js::from($canEdit) }},
        syncIssueMessage: {{ Js::from($syncIssueMessage) }},
        settings: {{ Js::from($settings) }},

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
            if (!this.canEdit) {
                return false;
            }

            return String(this.settings.main_contract_number || '').trim() !== '';
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
                <div :class="{ 'yd-direct-error-toggle': hasIntegrationError && settings.is_enabled }">
                    <x-form.toggle-switch
                        x-model="settings.is_enabled"
                        x-bind:disabled="!canEdit"
                    ></x-form.toggle-switch>
                </div>
                <span
                    class="text-secondary-text text-sm"
                    x-show="settings.is_enabled"
                    x-text="syncEnabledLabel"
                    x-cloak
                ></span>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label
                required
                tooltip="Номер основного договора из 1С. По нему Касини находит акты для этого клиенто-проекта."
                tooltip-panel-max-width="22rem"
            >
                Номер основного договора
            </x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text
                    x-model="settings.main_contract_number"
                    x-bind:disabled="!canEdit"
                ></x-form.input-text>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <div class="flex gap-3">
                <label class="text-sm max-w-[250px]">Номер доп. соглашения</label>
                <x-overlay.tooltip panel-max-width="22rem">
                    Нужно указать только число, без префиксов. Например, не «ДС-7», а «7».<br>
                    Если доп. соглашений несколько - укажите все номера через запятую.<br>
                    Если номер доп. соглашения изменился - добавьте новый номер через запятую.
                </x-overlay.tooltip>
            </div>
            <div class="w-[305px]">
                <x-form.input-text
                    x-model="settings.additional_agreement_number"
                    x-bind:disabled="!canEdit"
                ></x-form.input-text>
            </div>
        </x-form.form-field>
    </x-form.form>

    <x-project-form.integration-modal-footer class="mt-auto" />
</div>
