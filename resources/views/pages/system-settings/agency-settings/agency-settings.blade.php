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
                        @if (! empty($admin['id']))
                            <a
                                href="{{ route('system-settings.users.edit', ['user' => $admin['id']]) }}"
                                wire:navigate
                                class="border-primary text-primary cursor-pointer rounded border bg-blue-50 px-2 py-1 transition hover:bg-blue-100"
                            >
                                {{ $admin['name'] }}
                            </a>
                        @else
                            <span
                                class="border-primary text-primary rounded border bg-blue-50 px-2 py-1"
                            >
                                {{ $admin['name'] }}
                            </span>
                        @endif
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

            <h2 class="mb-1 mt-6 font-semibold">Интеграция с Битрикс24</h2>

            <x-form.form-field>
                <x-form.form-label>URL портала Битрикс24</x-form.form-label>
                <x-form.input-text
                    wire:model="form.bitrix24PortalUrl"
                    placeholder="https://company.bitrix24.ru"
                />
            </x-form.form-field>

            <x-form.form-field>
                <div class="flex gap-3">
                    <label class="max-w-[250px] text-sm">Вебхук</label>
                    <x-overlay.tooltip panel-max-width="20rem">
                        В Битрикс24: войти администратором → Приложения → Разработчикам → Другое → Входящий вебхук.
                        Скопируйте адрес вебхука. В правах включите «Задачи» и «Пользователи»: по ним Касини позже
                        заберёт часы и сопоставит сотрудников.
                        <strong class="font-semibold not-italic">После настройки в настройках клиенто-проектов станет доступен Битрикс24 для настройки интеграции.</strong>
                        Вебхук — как пароль: не пересылайте его.
                    </x-overlay.tooltip>
                </div>
                <div
                    class="flex flex-col gap-2"
                    x-data="{
                        focused: false,
                        mask(value) {
                            if (! value) {
                                return '';
                            }
                            if (value.length <= 14) {
                                return '*'.repeat(value.length);
                            }
                            const head = value.slice(0, 8);
                            const tail = value.slice(-6);
                            const middleLen = value.length - head.length - tail.length;

                            return head + '*'.repeat(middleLen) + tail;
                        }
                    }"
                >
                    <textarea
                        rows="2"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="https://company.bitrix24.ru/rest/1/секрет/"
                        class="min-h-[68px] w-full resize-none rounded-[5px] border border-input-border pe-3 ps-3 py-2 font-mono text-sm leading-snug @error('form.bitrix24Webhook') border-warning-red @enderror"
                        x-bind:value="focused ? ($wire.form.bitrix24Webhook ?? '') : mask($wire.form.bitrix24Webhook ?? '')"
                        x-on:focus="focused = true; $nextTick(() => { $el.value = $wire.form.bitrix24Webhook ?? '' })"
                        x-on:blur="
                            $wire.set('form.bitrix24Webhook', $el.value);
                            focused = false;
                            $nextTick(() => { $el.value = mask($wire.form.bitrix24Webhook ?? '') });
                        "
                        x-on:input="if (focused) { $dispatch('agency-settings-mark-dirty') }"
                    ></textarea>
                    @error('form.bitrix24Webhook')
                        <span class="text-warning-red text-[12px]">{{ $message }}</span>
                    @enderror
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
                            class="border-secondary-text relative flex min-h-[200px] w-full cursor-pointer items-center justify-center rounded border p-4"
                            x-data
                            @click="$refs.logoInput.click()"
                        >
                            <img
                                class="max-h-[160px] max-w-full object-contain"
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
                            class="border-secondary-text relative flex min-h-[200px] w-full cursor-pointer items-center justify-center rounded border p-4"
                            x-data
                            @click="$refs.logoInput.click()"
                        >
                            <img
                                class="max-h-[160px] max-w-full object-contain"
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
