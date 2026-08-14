@props([
    'projectIntegration' => null,
    'projectId' => null,
    'platformConfigured' => true,
    'canEdit' => true,
])

@php
    use App\Models\Integration;
    use App\Models\IntegrationProject;
    use Illuminate\Support\Str;

    $savedSettings = $projectIntegration->settings ?? [];
    $getSetting = function (string $key, mixed $default = '') use ($savedSettings) {
        if (array_key_exists($key, $savedSettings)) {
            return $savedSettings[$key];
        }

        return $savedSettings[Str::camel($key)] ?? $default;
    };

    $syncEnabledAt = $getSetting('sync_enabled_at', '');

    if (($projectIntegration->isEnabled ?? false) && $syncEnabledAt === '' && $projectId) {
        $callibriIntegration = Integration::query()->where('code', 'callibri')->first();

        if ($callibriIntegration) {
            $integrationRecord = IntegrationProject::query()
                ->where('project_id', $projectId)
                ->where('integration_id', $callibriIntegration->id)
                ->first();

            if ($integrationRecord?->updated_at) {
                $syncEnabledAt = $integrationRecord->updated_at->format('Y-m-d');
            }
        }
    }

    $callibriSettings = [
        'is_enabled' => $projectIntegration->isEnabled ?? false,
        'email' => $getSetting('email', ''),
        'token' => $getSetting('token', ''),
        'site_id' => (string) $getSetting('site_id', ''),
        'sync_enabled_at' => $syncEnabledAt,
        'utm_filter_mode' => $getSetting('utm_filter_mode', 'none'),
        'utm_source' => $getSetting('utm_source', ''),
        'utm_medium' => $getSetting('utm_medium', ''),
        'utm_campaign' => $getSetting('utm_campaign', ''),
        'appeals_type' => $getSetting('appeals_type', []),
        'appeals_filter' => $getSetting('appeals_filter', 'all'),
        'lead_cost_calc' => $getSetting('lead_cost_calc', 'all'),
        'appeals_class' => $getSetting('appeals_class', ''),
    ];
@endphp

<div
    class="flex h-full w-fit min-w-0 flex-col"
    x-data="{
        canEdit: @js($canEdit),
        settings: {{ Js::from($callibriSettings) }},
        projectOptions: [],
        projectsLoading: false,
        projectsError: null,
        projectSelectOpen: false,
        testPanelOpen: false,
        testDate: '',
        testCount: null,
        testLoading: false,
        testError: null,

        init() {
            this.$watch('settings.is_enabled', (enabled) => {
                if (!enabled) {
                    this.settings.sync_enabled_at = '';
                } else if (!this.settings.sync_enabled_at) {
                    const today = new Date();
                    this.settings.sync_enabled_at = today.getFullYear()
                        + '-' + String(today.getMonth() + 1).padStart(2, '0')
                        + '-' + String(today.getDate()).padStart(2, '0');
                }
            });

            if (this.settings.email && this.settings.token) {
                this.loadProjects();
            }
        },

        get canSave() {
            const hasValidProject = this.settings.site_id !== ''
                && this.projectOptions.some(o => String(o.value) === String(this.settings.site_id));

            return this.settings.email.trim() !== ''
                && this.settings.token.trim() !== ''
                && hasValidProject
                && this.settings.appeals_type.length > 0;
        },

        get syncEnabledLabel() {
            if (!this.settings.sync_enabled_at) {
                return '';
            }

            const [y, m, d] = this.settings.sync_enabled_at.split('-');

            return `включена: ${d}.${m}.${y}`;
        },

        async loadProjects() {
            if (!this.settings.email.trim() || !this.settings.token.trim()) {
                return;
            }

            this.projectsLoading = true;
            this.projectsError = null;

            const result = await $wire.loadCallibriProjects(
                this.settings.email,
                this.settings.token,
                this.settings.site_id || null
            );

            if (result.error) {
                this.projectsError = result.error;
                this.projectOptions = [];
            } else {
                this.projectOptions = result.projects ?? [];
            }

            this.projectsLoading = false;
        },

        toggleProjectSelect() {
            if (this.projectsLoading || this.projectOptions.length === 0) {
                return;
            }

            this.projectSelectOpen = !this.projectSelectOpen;
        },

        selectProject(value) {
            this.settings.site_id = String(value);
            this.projectSelectOpen = false;
        },

        get projectSelectLabel() {
            if (this.projectsLoading && this.projectOptions.length === 0) {
                return 'Загрузка...';
            }

            if (this.projectOptions.length === 0) {
                return 'Нет доступных проектов';
            }

            const selected = this.projectOptions.find(
                o => String(o.value) === String(this.settings.site_id)
            );

            return selected ? selected.label : 'Выберите проект';
        },

        save() {
            if (!this.canEdit || !this.canSave) {
                return;
            }

            const payload = { ...this.settings };

            if (!payload.is_enabled) {
                delete payload.sync_enabled_at;
            } else if (!payload.sync_enabled_at) {
                const today = new Date();
                payload.sync_enabled_at = today.getFullYear()
                    + '-' + String(today.getMonth() + 1).padStart(2, '0')
                    + '-' + String(today.getDate()).padStart(2, '0');
            }

            $wire.setIntegrationSettings({{ $projectIntegration->integration->id }}, payload);
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        handleCancelClick() {
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },

        toggleTestPanel() {
            this.testPanelOpen = !this.testPanelOpen;

            if (!this.testPanelOpen) {
                return;
            }

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.$refs.callibriTestPanel?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'end',
                        inline: 'nearest',
                    });
                });
            });
        },

        async runTest() {
            if (!this.testDate) {
                this.testError = 'Укажите дату';
                return;
            }

            this.testLoading = true;
            this.testError = null;
            this.testCount = null;

            const result = await $wire.testCallibriIntegration(this.settings, this.testDate);

            if (result.error) {
                this.testError = result.error;
            } else {
                this.testCount = result.count;
            }

            this.testLoading = false;
        }
    }"
>
    <x-panel.scroll-panel style="max-height: 500px">
        <div class="border-primary mb-4 break-words rounded-lg border bg-blue-50 p-4 text-sm text-primary-text">
            Чтобы количество обращений в ЕЖЛ совпадало с количеством обращений в Касини, проверьте, чтобы в
            <a
                class="text-primary underline"
                href="{{ route('system-settings.agency.default') }}"
            >настройках агентства</a>
            поле «Основной часовой пояс агентства» совпадал с «Часовым поясом» в Callibri.
        </div>

        <x-form.form>
            <x-form.form-field>
                <x-form.form-label>Синхронизация</x-form.form-label>
                <div class="flex w-[305px] items-center gap-4">
                    <x-form.toggle-switch x-model="settings.is_enabled"></x-form.toggle-switch>
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
                    tooltip="Email пользователя Callibri с правами доступа к API"
                >Email</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.input-text
                        x-model="settings.email"
                        x-on:blur="loadProjects()"
                    ></x-form.input-text>
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label
                    required
                    tooltip="API токен из личного кабинета Callibri"
                >API токен</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.input-text
                        x-model="settings.token"
                        x-on:blur="loadProjects()"
                    ></x-form.input-text>
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label required>Выберите проект</x-form.form-label>
                <div class="w-[305px]">
                    <div class="text-input-text relative select-none">
                        <div class="group" x-ref="projectSelectButton">
                            <div
                                class="border-input-border flex min-h-[42px] w-full items-center rounded-[5px] border pe-10 ps-4"
                                x-ref="projectSelectTrigger"
                                x-on:click="toggleProjectSelect()"
                                x-bind:class="{
                                    'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': projectSelectOpen,
                                    'rounded-[5px]': !projectSelectOpen,
                                    'bg-secondary': projectsLoading,
                                    'opacity-70': !projectsLoading && projectOptions.length === 0
                                }"
                            >
                                <span
                                    class="overflow-hidden"
                                    x-text="projectSelectLabel"
                                    x-bind:class="{
                                        'opacity-50': !settings.site_id && projectOptions.length > 0,
                                        'text-gray-400 italic': projectOptions.length === 0
                                    }"
                                ></span>
                            </div>

                            <template x-if="!projectsLoading && projectOptions.length > 0">
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                                    <x-icons.arrow
                                        class="transition-transform duration-300"
                                        x-bind:class="{
                                            'rotate-180 group-hover:text-white': projectSelectOpen,
                                        }"
                                    />
                                </span>
                            </template>
                        </div>

                        <div
                            class="z-1000 border-input-border max-h-52 w-full overflow-y-auto rounded-b-[5px] border border-t-0"
                            x-cloak
                            x-show="projectSelectOpen && projectOptions.length > 0"
                            x-anchor.no-style="$refs.projectSelectButton"
                            x-bind:style="{ position: 'absolute', top: $anchor.y + 'px' }"
                            x-on:click.outside="projectSelectOpen = false"
                        >
                            <template x-for="option in projectOptions" :key="option.value">
                                <div
                                    class="hover:bg-primary flex min-h-[42px] cursor-pointer items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white"
                                    x-on:click="selectProject(option.value)"
                                    x-text="option.label"
                                ></div>
                            </template>
                        </div>
                    </div>
                    <p class="text-warning-red mt-1 text-xs" x-show="projectsError" x-text="projectsError" x-cloak></p>
                </div>
            </x-form.form-field>

            <x-form.form-field class="items-start">
                <x-form.form-label
                    class="max-w-[250px] break-words leading-snug"
                    tooltip="Выберите режим фильтрации обращений по UTM-меткам"
                >Нужно ли забирать обращения с определенных UTM-меток?</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.select
                        :options="[
                            ['value' => 'none', 'label' => 'Не фильтровать'],
                            ['value' => 'source', 'label' => 'UTM Source'],
                            ['value' => 'medium', 'label' => 'UTM Medium'],
                            ['value' => 'campaign', 'label' => 'UTM Campaign'],
                        ]"
                        x-model="settings.utm_filter_mode"
                    ></x-form.select>
                </div>
            </x-form.form-field>

            <x-form.form-field x-show="settings.utm_filter_mode === 'source'" x-cloak>
                <x-form.form-label
                    tooltip="Несколько значений через запятую. Пустое поле — все обращения с заполненным UTM Source"
                >UTM Source фильтр</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.input-text x-model="settings.utm_source"></x-form.input-text>
                </div>
            </x-form.form-field>

            <x-form.form-field x-show="settings.utm_filter_mode === 'medium'" x-cloak>
                <x-form.form-label
                    tooltip="Несколько значений через запятую. Пустое поле — все обращения с заполненным UTM Medium"
                >UTM Medium фильтр</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.input-text x-model="settings.utm_medium"></x-form.input-text>
                </div>
            </x-form.form-field>

            <x-form.form-field x-show="settings.utm_filter_mode === 'campaign'" x-cloak>
                <x-form.form-label
                    tooltip="Несколько значений через запятую. Пустое поле — все обращения с заполненным UTM Campaign"
                >UTM Campaign фильтр</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.input-text x-model="settings.utm_campaign"></x-form.input-text>
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label required>Типы обращений</x-form.form-label>
                <div class="flex w-[305px] flex-col gap-3">
                    <label class="flex items-center gap-2 text-sm">
                        <x-form.checkbox value="calls" x-model="settings.appeals_type"></x-form.checkbox>
                        Звонки
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <x-form.checkbox value="chats" x-model="settings.appeals_type"></x-form.checkbox>
                        Чаты
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <x-form.checkbox value="emails" x-model="settings.appeals_type"></x-form.checkbox>
                        Email
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <x-form.checkbox value="requests" x-model="settings.appeals_type"></x-form.checkbox>
                        Заявки
                    </label>
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Какие обращения получать?</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.select
                        :options="[
                            ['value' => 'all', 'label' => 'Все обращения'],
                            ['value' => 'first_only', 'label' => 'Только первичные'],
                        ]"
                        x-model="settings.appeals_filter"
                    ></x-form.select>
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Учёт класса обращений</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.select
                        :options="[
                            ['value' => 'all', 'label' => 'Все обращения'],
                            ['value' => 'selected_classes_only', 'label' => 'Только выбранные классы'],
                        ]"
                        x-model="settings.lead_cost_calc"
                    ></x-form.select>
                </div>
            </x-form.form-field>

            <x-form.form-field x-show="settings.lead_cost_calc === 'selected_classes_only'" x-cloak>
                <x-form.form-label
                    tooltip="Несколько классов через запятую. Префикс ! исключает класс, например: !спам"
                >Классы обращений</x-form.form-label>
                <div class="w-[305px]">
                    <x-form.input-text x-model="settings.appeals_class"></x-form.input-text>
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <div
                    class="flex cursor-pointer items-center gap-3 self-start text-primary"
                    x-on:click="toggleTestPanel()"
                    x-bind:aria-expanded="testPanelOpen"
                >
                    <x-button.button
                        class="pointer-events-none self-start"
                        type="button"
                        variant="action"
                        wrap
                        label="Проверить работу интеграции"
                    />
                    <span
                        class="inline-flex shrink-0 rotate-270 transition-transform duration-300"
                        x-bind:class="{ 'rotate-90': testPanelOpen, 'rotate-270': !testPanelOpen }"
                    >
                        <x-icons.arrow-left />
                    </span>
                </div>
                <span class="w-[305px]" aria-hidden="true"></span>
            </x-form.form-field>

            <div
                class="flex flex-col gap-3"
                x-ref="callibriTestPanel"
                x-show="testPanelOpen"
                x-cloak
            >
                <x-form.form-field>
                    <x-form.form-label>Дата</x-form.form-label>
                    <div class="flex w-[305px] items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <x-form.date-picker
                                class="w-full"
                                placeholder="дд.мм.гггг"
                                x-model="testDate"
                            ></x-form.date-picker>
                        </div>
                        <x-button.button
                            size="sm"
                            label="Проверить"
                            x-bind:disabled="testLoading"
                            x-on:click="runTest()"
                        />
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <span class="invisible text-sm" aria-hidden="true">Дата</span>
                    <div class="w-[305px]">
                        <span class="text-sm" x-show="testCount !== null" x-text="'Обращений: ' + testCount" x-cloak></span>
                        <p class="text-warning-red mt-1 text-xs" x-show="testError" x-text="testError" x-cloak></p>
                    </div>
                </x-form.form-field>
            </div>
        </x-form.form>
    </x-panel.scroll-panel>

    <x-project-form.integration-modal-footer class="mt-4" />
</div>
