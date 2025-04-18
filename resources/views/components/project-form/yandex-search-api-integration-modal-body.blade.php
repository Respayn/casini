@props([
    'projectIntegration' => null,
])


<div
    class="flex h-full flex-col"
    x-data="{
        settings: {
            is_enabled: {{ Js::from($projectIntegration->isEnabled ?? false) }},
            regions: []
        },
    
        addRegion() {
            const region = {
                code: null,
                phrases: []
            };
            this.settings.regions.push(region);
        },
    
        removeRegion(index) {
            this.settings.regions.splice(index, 1);
        },
    
        save() {
            $wire.setIntegrationSettings({{ $projectIntegration->integration->id }}, this.settings);
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        },
    
        handleCancelClick() {
            $dispatch('modal-hide', { name: 'integration-settings-modal' });
        }
    }"
>
    <x-panel.scroll-panel style="max-height: 400px">
        <x-form.form>
            <x-form.form-field class="w-[603px]">
                <x-form.form-label>Синхронизация</x-form.form-label>
                <div>
                    <x-form.toggle-switch x-model="settings.is_enabled"></x-form.toggle-switch>
                </div>
            </x-form.form-field>

            <div x-text="JSON.stringify(settings.regions)"></div>

            <template x-for="region in settings.regions">
                <div>
                    <x-form.form-field class="w-[603px]">
                        <x-form.form-label>Регион</x-form.form-label>
                        <div class="w-[305px]">
                            <x-form.select
                                :options="App\Enums\SearchRegion::options()"
                                x-model="region.code"
                            ></x-form.select>
                        </div>
                    </x-form.form-field>

                    <div>Список фраз</div>
                    <div>Компонент</div>
                    <div>Инпут</div>
                    <div>Загрузить список фраз в .docx</div>
                </div>

            </template>

            <div>
                <x-button.button
                    variant="link"
                    label="Добавить регион"
                    x-on:click="addRegion"
                ></x-button.button>
            </div>
        </x-form.form>
    </x-panel.scroll-panel>
    <div class="mt-auto flex justify-between">
        <x-button.button
            variant="primary"
            label="Сохранить изменения"
            x-on:click="save"
        />
        <x-button.button
            label="Отменить"
            x-on:click="handleCancelClick"
        />
    </div>
</div>
