<div>
    <x-menu.back-button />

    <x-form.form
        :is-normalized="true"
        wire:submit.prevent="save"
    >
        <div class="mt-4 flex max-w-[950px] flex-col gap-4">
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

            <h2 class="mb-1 mt-6 font-semibold">Реквизиты в отчетах</h2>

            <x-form.form-field>
                <x-form.form-label tooltip="Адрес сайта будет отображаться в отчетах">URL-адрес сайта
                    агентства</x-form.form-label>
                <x-form.input-text
                    wire:model="form.url"
                    placeholder="https://siteactiv.ru"
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
                <div class="flex max-w-[305px] flex-col items-start gap-2">
                    @if ($form->logo)
                        {{-- Превью до сохранения --}}
                        <img
                            class="border-secondary-text w-full rounded border object-contain"
                            src="{{ $form->logo->temporaryUrl() }}"
                            alt="Превью логотипа"
                        />
                        <x-button.button
                            class="text-secondary-text w-full !px-3 !py-1 text-sm"
                            type="button"
                            wire:click="deleteLogo"
                            variant="link"
                        >
                            <x-slot:label>Удалить</x-slot:label>
                        </x-button.button>
                    @elseif($form->logoSrc)
                        {{-- Уже сохранённый логотип --}}
                        <div>
                            <img
                                class="border-secondary-text w-full rounded border object-contain"
                                src="{{ Storage::url($form->logoSrc) }}"
                                alt="Логотип агентства"
                            />
                            <x-button.button
                                class="text-secondary-text w-full !px-3 !py-1 text-sm"
                                type="button"
                                wire:click="deleteLogo"
                                variant="link"
                            >
                                <x-slot:label>Удалить</x-slot:label>
                            </x-button.button>
                        </div>
                    @endif

                    {{-- Поле для загрузки всегда отображается если нет превью --}}
                    @if (!$form->logo && !$form->logoSrc)
                        <label
                            class="border-secondary-text flex min-h-[305px] w-full cursor-pointer flex-col items-center justify-center rounded border object-contain transition hover:bg-gray-50"
                        >
                            <x-icons.camera class="text-secondary-text mb-2 h-10 w-10" />
                            <x-form.input-file
                                class="hidden"
                                accept=".jpg,.jpeg,.png"
                                wire:model="form.logo"
                            />
                            <span class="text-secondary-text mt-2 block w-full text-center text-sm">
                                Загрузить фото
                            </span>
                        </label>
                    @endif

                    <span class="text-secondary-text text-xs">Только jpg, jpeg, png. До 1 Мб.</span>
                </div>
            </x-form.form-field>

            <div class="mt-4 flex justify-between gap-4">
                <x-button.button
                    type="submit"
                    variant="primary"
                    :disabled="!$form->name || !$form->timeZone"
                >
                    <x-slot:label>Сохранить</x-slot:label>
                </x-button.button>
                <x-button.button
                    type="button"
                    variant="secondary"
                    wire:click="$refresh"
                >
                    <x-slot:label>Отменить</x-slot:label>
                </x-button.button>
            </div>
        </div>
    </x-form.form>
</div>
