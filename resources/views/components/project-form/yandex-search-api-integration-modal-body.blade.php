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
        $searchApiIntegration = Integration::query()->where('code', 'yandex_search_api')->first();

        if ($searchApiIntegration) {
            $integrationRecord = IntegrationProject::query()
                ->where('project_id', $projectId)
                ->where('integration_id', $searchApiIntegration->id)
                ->first();

            if ($integrationRecord?->updated_at) {
                $syncEnabledAt = $integrationRecord->updated_at->format('Y-m-d');
            }
        }
    }

    $savedRegions = $getSetting('regions', []);
    if (! is_array($savedRegions)) {
        $savedRegions = [];
    }

    $searchApiSettings = [
        'is_enabled' => $projectIntegration->isEnabled ?? false,
        'sync_enabled_at' => $syncEnabledAt,
        'regions' => $savedRegions,
    ];
@endphp

<div
    class="flex h-full w-fit min-w-0 flex-col gap-5"
    x-data="{
        canEdit: @js($canEdit),
        platformConfigured: {{ Js::from($platformConfigured) }},
        settings: {{ Js::from($searchApiSettings) }},
        docxUploading: false,
        docxError: null,

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
                    const today = new Date();
                    this.settings.sync_enabled_at = today.getFullYear()
                        + '-' + String(today.getMonth() + 1).padStart(2, '0')
                        + '-' + String(today.getDate()).padStart(2, '0');
                }
            });
        },

        normalizePhrase(phrase) {
            return phrase.trim().toLowerCase();
        },

        get duplicatePhraseKeys() {
            const seen = {};
            const duplicates = new Set();

            this.settings.regions.forEach((region) => {
                (region.phrases || []).forEach((phrase) => {
                    const key = this.normalizePhrase(phrase);
                    if (key === '') {
                        return;
                    }
                    if (seen[key]) {
                        duplicates.add(key);
                    } else {
                        seen[key] = true;
                    }
                });
            });

            return duplicates;
        },

        isPhraseDuplicate(regionIndex, phraseIndex) {
            const phrase = this.settings.regions[regionIndex]?.phrases?.[phraseIndex] ?? '';
            const key = this.normalizePhrase(phrase);
            if (key === '') {
                return false;
            }

            let count = 0;
            this.settings.regions.forEach((region) => {
                (region.phrases || []).forEach((item) => {
                    if (this.normalizePhrase(item) === key) {
                        count++;
                    }
                });
            });

            return count > 1;
        },

        get canSave() {
            if (this.settings.is_enabled && !this.platformConfigured) {
                return false;
            }
            if (this.settings.regions.length === 0) {
                return false;
            }
            if (this.duplicatePhraseKeys.size > 0) {
                return false;
            }

            return this.settings.regions.every((region) =>
                region.code !== null
                && region.code !== ''
                && (region.phrases || []).length >= 1
                && (region.phrases || []).every((phrase) => phrase.trim() !== '')
            );
        },

        get syncEnabledLabel() {
            if (!this.settings.sync_enabled_at) {
                return '';
            }

            const [y, m, d] = this.settings.sync_enabled_at.split('-');
            return `включена: ${d}.${m}.${y}`;
        },

        addRegion() {
            this.settings.regions.push({
                code: null,
                phrases: [''],
            });
        },

        removeRegion(index) {
            if (this.settings.regions.length <= 1) {
                return;
            }
            this.settings.regions.splice(index, 1);
        },

        addPhrase(regionIndex) {
            this.settings.regions[regionIndex].phrases.push('');
        },

        removePhrase(regionIndex, phraseIndex) {
            const phrases = this.settings.regions[regionIndex].phrases;
            if (phrases.length <= 1) {
                return;
            }
            phrases.splice(phraseIndex, 1);
        },

        async uploadDocx(regionIndex, event) {
            const file = event.target.files[0];
            if (!file) {
                return;
            }

            this.docxError = null;
            this.docxUploading = true;

            await new Promise((resolve, reject) => {
                $wire.upload(
                    'phraseDocxFile',
                    file,
                    async () => {
                        try {
                            const result = await $wire.parsePhrasesFromDocx();
                            resolve(result);
                        } catch (error) {
                            reject(error);
                        }
                    },
                    (error) => reject(error)
                );
            }).then((result) => {
                if (result.error) {
                    this.docxError = result.error;
                    return;
                }

                const existing = this.settings.regions[regionIndex].phrases.filter((p) => p.trim() !== '');
                this.settings.regions[regionIndex].phrases = [...existing, ...result.phrases];

                if (this.settings.regions[regionIndex].phrases.length === 0) {
                    this.settings.regions[regionIndex].phrases = [''];
                }
            }).catch(() => {
                this.docxError = 'Не удалось загрузить файл .docx';
            }).finally(() => {
                this.docxUploading = false;
                event.target.value = '';
            });
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
    }"
>
    <x-panel.scroll-panel style="max-height: 500px">
        @unless ($platformConfigured)
            <p class="text-warning-red mb-4 text-sm">
                Интеграция Yandex Search API не настроена на сервере. Обратитесь к администратору.
            </p>
        @endunless

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

            <template x-for="(region, regionIndex) in settings.regions" :key="regionIndex">
                <div class="mb-6 border-b border-gray-100 pb-6 last:border-b-0">
                    <div class="mb-4 flex items-start justify-between gap-2">
                        <x-form.form-label required>Отслеживание позиций в Яндексе</x-form.form-label>
                        <button
                            type="button"
                            class="text-secondary-text shrink-0 p-1"
                            x-show="settings.regions.length > 1"
                            x-on:click="removeRegion(regionIndex)"
                        >
                            <x-icons.close class="h-4 w-4" />
                        </button>
                    </div>

                    <x-form.form-field class="mb-4">
                        <div class="w-[305px]">
                            <x-form.select
                                :options="App\Enums\SearchRegion::options()"
                                placeholder="Выберите регион"
                                x-model="region.code"
                            ></x-form.select>
                        </div>
                    </x-form.form-field>

                    <template x-for="(phrase, phraseIndex) in region.phrases" :key="phraseIndex">
                        <div class="mb-2 flex items-center gap-2">
                            <div class="w-[305px]">
                                <x-form.input-text
                                    x-model="region.phrases[phraseIndex]"
                                    x-bind:class="isPhraseDuplicate(regionIndex, phraseIndex) ? 'border-warning-red' : ''"
                                ></x-form.input-text>
                            </div>
                            <button
                                type="button"
                                class="text-secondary-text shrink-0 p-1"
                                x-show="region.phrases.length > 1"
                                x-on:click="removePhrase(regionIndex, phraseIndex)"
                            >
                                <x-icons.close class="h-4 w-4" />
                            </button>
                        </div>
                    </template>

                    <div class="mt-2 flex flex-col items-start gap-2">
                        <x-button.button
                            variant="link"
                            label="Добавить еще фразу +"
                            x-on:click="addPhrase(regionIndex)"
                        />
                        <div class="flex items-center gap-2">
                            <x-button.button
                                variant="link"
                                label="Загрузить список фраз в .docx"
                                x-on:click="document.getElementById('docx-input-' + regionIndex).click()"
                            />
                            <x-overlay.tooltip>
                                Формат загружаемого файла .docx; в файле должны быть только фразы; каждая фраза
                                должна начинаться с новой строки; после загрузки фразы отобразятся в окне настройки
                                интеграции — перед сохранением проверьте правильность фраз; дубли фраз будут
                                подсвечены как ошибка.
                            </x-overlay.tooltip>
                        </div>
                        <input
                            type="file"
                            accept=".docx"
                            class="hidden"
                            x-bind:id="'docx-input-' + regionIndex"
                            x-on:change="uploadDocx(regionIndex, $event)"
                        />
                    </div>
                </div>
            </template>

            <p class="text-warning-red mb-2 text-sm" x-show="docxError" x-text="docxError" x-cloak></p>
            <p class="text-secondary-text mb-4 text-sm" x-show="docxUploading" x-cloak>Загрузка файла…</p>

            <div>
                <x-button.button
                    variant="outlined"
                    label="Добавить регион"
                    x-on:click="addRegion"
                />
            </div>
        </x-form.form>
    </x-panel.scroll-panel>

    <x-project-form.integration-modal-footer />
</div>
