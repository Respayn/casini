<div>
    <x-menu.back-button />

    <x-form.form :is-normalized="true" wire:submit.prevent="save">
        <div class="mt-4 flex max-w-[950px] flex-col gap-4">
            <h1 class="text-xl font-semibold">Настройка агентства</h1>

            <h2 class="font-semibold mt-2 mb-1">Основные настройки</h2>

            <x-form.form-field>
                <x-form.form-label> ID агентства </x-form.form-label>
                <x-form.input-text :value="$form->id" disabled class="bg-gray-100"></x-form.input-text>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label required>Название агентства</x-form.form-label>
                <x-form.input-text wire:model="form.name" />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label tooltip="Список админов формируется автоматически по ролям">Пользователи с ролью администратор</x-form.form-label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($admins as $admin)
                        <span class="rounded border border-primary px-2 py-1 text-primary bg-blue-50 cursor-pointer hover:bg-blue-100 transition">
                            {{ $admin['name'] }}
                        </span>
                    @endforeach
                </div>
{{--                <span class="text-xs text-secondary-text mt-1">--}}
{{--                    Список редактируется в разделе <b>Пользователи и роли</b>--}}
{{--                </span>--}}
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label required tooltip="Выбранный часовой пояс влияет на отображение дат и автоматизацию процессов">
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

            <h2 class="font-semibold mt-6 mb-1">Реквизиты в отчетах</h2>

            <x-form.form-field>
                <x-form.form-label tooltip="Адрес сайта будет отображаться в отчетах">URL-адрес сайта агентства</x-form.form-label>
                <x-form.input-text wire:model="form.url" placeholder="https://siteactiv.ru"/>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Email агентства</x-form.form-label>
                <x-form.input-text wire:model="form.email" placeholder="email@siteactiv.ru"/>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Телефон агентства</x-form.form-label>
                <x-form.input-text wire:model="form.phone" placeholder="+7 (343) 317-22-30"/>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Фактический адрес агентства</x-form.form-label>
                <x-form.input-text wire:model="form.address" placeholder="Центральный офис: г. Екатеринбург, ул. Добролюбова 16/2, оф.201"/>
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label tooltip="Можно загружать только jpg, jpeg, png, gif. До 1 Мб">Логотип агентства</x-form.form-label>
                <div class="flex flex-col gap-2 items-start max-w-[305px]">
                    @if($form->logo)
                        {{-- Превью до сохранения --}}
                        <img src="{{ $form->logo->temporaryUrl() }}" alt="Превью логотипа" class="w-full object-contain rounded border border-secondary-text" />
                        <x-button.button
                            type="button"
                            wire:click="deleteLogo"
                            variant="link"
                            class="!py-1 !px-3 text-sm w-full text-secondary-text"
                        >
                            <x-slot:label>Удалить</x-slot:label>
                        </x-button.button>
                    @elseif($form->logoSrc)
                        {{-- Уже сохранённый логотип --}}
                        <div>
                            <img src="{{ Storage::url($form->logoSrc) }}" alt="Логотип агентства" class="w-full object-contain rounded border border-secondary-text" />
                            <x-button.button
                                type="button"
                                wire:click="deleteLogo"
                                variant="link"
                                class="!py-1 !px-3 text-sm w-full text-secondary-text"
                            >
                                <x-slot:label>Удалить</x-slot:label>
                            </x-button.button>
                        </div>
                    @endif

                    {{-- Поле для загрузки всегда отображается если нет превью --}}
                    @if(!$form->logo && !$form->logoSrc)
                        <x-form.input-file wire:model="form.logo" accept=".jpg,.jpeg,.png,.gif" class="w-full min-h-[200px] object-contain rounded border border-secondary-text"/>
                    @endif

                    <span class="text-xs text-secondary-text">Только jpg, jpeg, png, gif. До 1 Мб.</span>
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
