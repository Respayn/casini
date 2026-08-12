<div
    x-data="{
        hasPendingChanges: @js($startWithPendingChanges),
        successMessage: @js($startWithSuccessMessage) ? 'Изменения сохранены' : null,
        markDirty() {
            this.hasPendingChanges = true;
            this.successMessage = null;
        }
    }"
    x-on:input.capture="markDirty()"
    x-on:change.capture="markDirty()"
    x-on:agency-settings-mark-dirty.window="markDirty()"
>
    <x-menu.back-button />

    <div
        x-show="successMessage"
        x-cloak
        class="border-primary text-primary-text mt-4 mb-4 max-w-[950px] break-words rounded-lg border bg-blue-50 p-4 text-sm"
        x-text="successMessage"
    ></div>

    <x-panel.scroll-panel
        class="mb-3 mt-4"
        style="max-height: calc(100vh - 300px);"
    >
        <x-form.form
            :is-normalized="true"
            wire:submit.prevent="save"
        >
            <div class="flex max-w-[950px] flex-col gap-4">
                <h1 class="text-xl font-semibold">Настройка агентства</h1>

            <h2 class="mb-1 mt-2 font-semibold">Основные настройки</h2>

            <x-form.form-field>
                <x-form.form-label> ID агентства </x-form.form-label>
                <x-form.input-text
                    class="bg-gray-100"
                    :value="$form->id"
                    disabled
                ></x-form.input-text>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label required>Название агентства</x-form.form-label>
                <x-form.input-text wire:model="form.name" />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label tooltip="Список админов формируется автоматически по ролям">Пользователи с ролью
                    администратор</x-form.form-label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($form->admins as $admin)
                        <span
                            class="border-primary text-primary cursor-pointer rounded border bg-blue-50 px-2 py-1 transition hover:bg-blue-100"
                        >
                            {{ $admin['name'] }}
                        </span>
                    @endforeach
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label
                    required
                    tooltip="Выбранный часовой пояс влияет на отображение дат и автоматизацию процессов"
                >
                    Основной часовой пояс агентства
                </x-form.form-label>
                <div>
                    <x-form.select
                        :options="\App\Dictionaries\TimeZoneDictionary::optionsForSelect()"
                        wire:model="form.timeZone"
                        placeholder="Выберите значение"
                    />
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label
                    required
                    tooltip="Время считается по выбранному часовому поясу агентства. Обновление выполняется автоматически 1 раз в сутки"
                >
                    Время обновления «Остаток бюджета в Директе»
                </x-form.form-label>
                <div>
                    <x-form.input-text
                        type="time"
                        wire:model="form.directBudgetRefreshTime"
                    />
                </div>
            </x-form.form-field>

            <h2 class="mb-1 mt-6 font-semibold">Реквизиты в отчетах</h2>

            <x-form.form-field>
                <x-form.form-label tooltip="Адрес сайта будет отображаться в отчетах">URL-адрес сайта
                    агентства</x-form.form-label>
                <x-form.input-url
                    wire:model="form.url"
                    placeholder="siteactiv.ru"
                />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Email агентства</x-form.form-label>
                <x-form.input-text
                    wire:model="form.email"
                    placeholder="email@siteactiv.ru"
                />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Телефон агентства</x-form.form-label>
                <x-form.input-text
                    wire:model="form.phone"
                    placeholder="+7 (343) 317-22-30"
                />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Фактический адрес агентства</x-form.form-label>
                <div>
                    <x-form.textarea
                        wire:model="form.address"
                        placeholder="Центральный офис: г. Екатеринбург, ул. Добролюбова 16/2, оф.201"
                    />
                </div>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label tooltip="Можно загружать только jpg, jpeg, png, gif. До 1 Мб">Логотип
                    агентства</x-form.form-label>
                <div class="flex w-full flex-col items-stretch gap-1">
                    @if ($form->logo)
                        {{-- Превью до сохранения: клик по логотипу — выбрать другой --}}
                        <div
                            class="relative w-full cursor-pointer"
                            x-data
                            @click="$refs.logoInput.click()"
                        >
                            <img
                                class="border-secondary-text min-h-[200px] w-full rounded border object-contain"
                                src="{{ $form->logo->temporaryUrl() }}"
                                alt="Превью логотипа"
                            />
                            <input
                                x-ref="logoInput"
                                type="file"
                                accept=".jpg,.jpeg,.png,.gif"
                                class="sr-only"
                                wire:model="form.logo"
                                @click.stop
                            />
                        </div>
                        <x-button.button
                            type="button"
                            wire:click="deleteLogo"
                            icon="icons.delete"
                            class="w-full"
                            label="Удалить логотип"
                        />
                    @elseif ($form->logoSrc)
                        {{-- Уже сохранённый логотип: клик — выбрать другой --}}
                        <div
                            class="relative w-full cursor-pointer"
                            x-data
                            @click="$refs.logoInput.click()"
                        >
                            <img
                                class="border-secondary-text min-h-[200px] w-full rounded border object-contain"
                                src="{{ Storage::url($form->logoSrc) }}"
                                alt="Логотип агентства"
                            />
                            <input
                                x-ref="logoInput"
                                type="file"
                                accept=".jpg,.jpeg,.png,.gif"
                                class="sr-only"
                                wire:model="form.logo"
                                @click.stop
                            />
                        </div>
                        <x-button.button
                            type="button"
                            wire:click="deleteLogo"
                            icon="icons.delete"
                            class="w-full"
                            label="Удалить логотип"
                        />
                    @else
                        <div
                            class="border-secondary-text relative flex min-h-[305px] w-full cursor-pointer flex-col items-center justify-center rounded border object-contain transition hover:bg-gray-50"
                            x-data
                            @click="$refs.logoInput.click()"
                        >
                            <x-icons.camera class="text-secondary-text mb-2 h-10 w-10" />
                            <input
                                x-ref="logoInput"
                                type="file"
                                accept=".jpg,.jpeg,.png,.gif"
                                class="sr-only"
                                wire:model="form.logo"
                                @click.stop
                            />
                            <span class="text-secondary-text mt-2 block w-full text-center text-sm">
                                Загрузить логотип
                            </span>
                        </div>
                    @endif
                </div>
            </x-form.form-field>
            </div>
        </x-form.form>
    </x-panel.scroll-panel>

    <template x-if="hasPendingChanges">
        <div class="flex max-w-[950px] justify-between gap-4">
            <x-button.button
                type="button"
                variant="primary"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                :disabled="! $this->canSubmitAgencySettings"
            >
                <x-slot:label>Сохранить</x-slot:label>
            </x-button.button>
            <x-button.button
                type="button"
                x-on:click="$wire.cancelChanges()"
            >
                <x-slot:label>Отменить</x-slot:label>
            </x-button.button>
        </div>
    </template>
</div>
