@props([
    'projectIntegration' => null,
    'projectId' => null,
    'platformConfigured' => true,
])

@php
    $savedSettings = $projectIntegration->settings ?? [];
    $agencyHint = 'Сначала укажите URL портала и вебхук в настройках агентства';
    $rootTaskUrlError = \App\Support\Bitrix24ProjectSettingsValidator::ROOT_TASK_URL_ERROR;
@endphp

<div
    class="flex h-full flex-col"
    x-data="{
        platformConfigured: {{ Js::from($platformConfigured) }},
        settings: {
            is_enabled: {{ Js::from($projectIntegration->isEnabled ?? false) }},
            sync_enabled_at: {{ Js::from($savedSettings['sync_enabled_at'] ?? '') }},
            root_task: {{ Js::from($savedSettings['root_task'] ?? '') }},
            search_query: {{ Js::from($savedSettings['search_query'] ?? '') }},
            parse_comment_works: {{ Js::from((bool) ($savedSettings['parse_comment_works'] ?? false)) }},
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

        isValidHttpUrl(value) {
            const trimmed = String(value || '').trim();
            if (trimmed === '') {
                return false;
            }

            try {
                const url = new URL(trimmed);
                return url.protocol === 'http:' || url.protocol === 'https:';
            } catch (e) {
                return false;
            }
        },

        get rootTaskUrlError() {
            const trimmed = String(this.settings.root_task || '').trim();
            if (trimmed === '') {
                return '';
            }

            return this.isValidHttpUrl(trimmed) ? '' : {{ Js::from($rootTaskUrlError) }};
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

            const trimmed = String(this.settings.root_task || '').trim();
            if (trimmed === '' || this.rootTaskUrlError) {
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
        }
    }"
>
    <x-form.form>
        <x-form.form-field class="w-[603px]">
            <x-form.form-label>Синхронизация</x-form.form-label>
            <div class="flex w-[305px] items-center gap-4">
                @if ($platformConfigured)
                    <x-form.toggle-switch x-model="settings.is_enabled"></x-form.toggle-switch>
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

        <x-form.form-field>
            <x-form.form-label
                required
                tooltip="Задача в Битрикс24, внутри которой ведутся работы по этому клиенто-проекту. Вставьте ссылку на неё. Касини будет брать дочерние задачи и часы оттуда."
                tooltip-panel-max-width="22rem"
            >
                URL корневой задачи
            </x-form.form-label>
            <div class="flex w-[305px] flex-col gap-2">
                <textarea
                    rows="2"
                    x-model="settings.root_task"
                    placeholder="https://company.bitrix24.ru/.../tasks/task/view/123/"
                    x-bind:class="rootTaskUrlError ? 'border-warning-red' : 'border-input-border'"
                    class="min-h-[68px] w-full resize-none rounded-[5px] border pe-3 ps-3 py-2 text-sm leading-snug"
                ></textarea>
                <span
                    class="text-warning-red text-[12px]"
                    x-show="rootTaskUrlError"
                    x-text="rootTaskUrlError"
                    x-cloak
                ></span>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label
                tooltip="Дополнение к поиску дочерних задач. Пустое поле: все дочерние задачи корневой. Пример условий: SEO:, КР:"
                tooltip-panel-max-width="22rem"
            >
                Дополнение к строке поиска
            </x-form.form-label>
            <div class="w-[305px]">
                <textarea
                    rows="2"
                    x-model="settings.search_query"
                    placeholder="например, SEO:"
                    class="min-h-[68px] w-full resize-none rounded-[5px] border border-input-border pe-3 ps-3 py-2 text-sm leading-snug"
                ></textarea>
            </div>
        </x-form.form-field>

        <x-form.form-field class="w-[603px]">
            <x-form.form-label
                tooltip="Кроме «выполненных задач» в отчете за месяц будут сообщения из задачи месяца, начинающиеся со спецсимвола 💪"
                tooltip-panel-max-width="24rem"
            >
                Парсить работы из комментариев
            </x-form.form-label>
            <div class="flex w-[305px] items-center gap-4">
                <x-form.toggle-switch x-model="settings.parse_comment_works"></x-form.toggle-switch>
            </div>
        </x-form.form-field>
    </x-form.form>

    <x-project-form.integration-modal-footer class="mt-auto border-0 pt-0" />
</div>
