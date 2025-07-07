<x-form.form :is-normalized="true" wire:submit.prevent="save" class="mt-4">
    <div class="flex flex-col gap-4">
        <h1 class="text-xl font-semibold">
            {{ isset($form->id) ? 'Редактировать пользователя' : 'Добавить пользователя' }}
        </h1>

        {{-- Основная информация --}}
        <h2 class="font-semibold mt-2 mb-1">Основная информация</h2>
        <div class="flex flex-col gap-4">
            @if($form->id)
                <x-form.form-field>
                    <x-form.form-label>ID</x-form.form-label>
                    <x-form.input-text :value="$form->id" disabled class="bg-gray-100" />
                </x-form.form-field>
            @endif

            <x-form.form-field>
                <x-form.form-label required tooltip="Уникальный логин для входа в систему">Логин</x-form.form-label>
                <x-form.input-text wire:model="form.login" placeholder="Логин" />
            </x-form.form-field>

            @if(!isset($form->id))
                <x-form.form-field>
                    <x-form.form-label required tooltip="Минимум 8 символов">Пароль</x-form.form-label>
                    <x-form.input-text type="password" wire:model="form.password" placeholder="Пароль" />
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label required>Повторить пароль</x-form.form-label>
                    <x-form.input-text type="password" wire:model="form.password_confirmation" placeholder="Повторите пароль" />
                </x-form.form-field>
            @endif

            <x-form.form-field>
                <x-form.form-label required tooltip="Пользователь сможет войти только если активен">Статус</x-form.form-label>
                <x-form.select
                    wire:model="form.is_active"
                    :options="[['label'=>'Активен','value'=>1],['label'=>'Неактивен','value'=>0]]"
                    placeholder="Выберите значение"
                />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label required tooltip="Определяет уровень доступа">Роль</x-form.form-label>
                <x-form.select
                    wire:model="form.role_id"
                    :options="$roles"
                    placeholder="Выберите роль"
                />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label tooltip="По умолчанию базовая ставка">Ставка</x-form.form-label>
                <x-form.select
                    wire:model="form.rate_id"
                    :options="collect($rates)->map(fn($r) => ['label' => $r->name, 'value' => $r->id])->values()->all()"
                    placeholder="Выберите ставку"
                />
            </x-form.form-field>
        </div>

        {{-- Контактная информация --}}
        <h2 class="font-semibold mt-6 mb-1">Контактная информация</h2>
        <div class="flex flex-col gap-4">

            <x-form.form-field>
                <x-form.form-label>Имя</x-form.form-label>
                <x-form.input-text wire:model="form.first_name" placeholder="Имя" />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Фамилия</x-form.form-label>
                <x-form.input-text wire:model="form.last_name" placeholder="Фамилия" />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label required tooltip="Email для авторизации и уведомлений">Email</x-form.form-label>
                <x-form.input-text wire:model="form.email" type="email" placeholder="email@siteactiv.ru" />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label>Телефон</x-form.form-label>
                <x-form.input-text wire:model="form.phone" placeholder="+7 (999) 123-45-67" />
            </x-form.form-field>

            <x-form.form-field>
                <x-form.form-label tooltip="Можно загружать только jpg, jpeg, png. До 2 Мб">Фото пользователя</x-form.form-label>
                <div class="flex flex-col gap-1 items-start max-w-[305px]">
                    @if($form->photo)
                        {{-- Превью до сохранения --}}
                        <img src="{{ $form->photo->temporaryUrl() }}" alt="Фото профиля"
                             class="w-full min-h-[200px] object-contain rounded border border-secondary-text" />
                        <x-button.button
                            type="button"
                            wire:click="deletePhoto"
                            variant="link"
                            class="!py-1 !px-3 text-sm w-full text-secondary-text"
                        >
                            <x-slot:label>Удалить</x-slot:label>
                        </x-button.button>
                    @elseif($form->image_path)
                        {{-- Уже сохранённое фото --}}
                        <img src="{{ Storage::url($form->image_path) }}" alt="Фото профиля"
                             class="w-full min-h-[200px] object-contain rounded border border-secondary-text" />
                        <x-button.button
                            type="button"
                            wire:click="deletePhoto"
                            variant="link"
                            class="!py-1 !px-3 text-sm w-full text-secondary-text"
                        >
                            <x-slot:label>Удалить</x-slot:label>
                        </x-button.button>
                    @endif

                    {{-- Поле для загрузки всегда отображается если нет превью --}}
                    @if(!$form->photo && !$form->image_path)
                        <label class="flex flex-col justify-center items-center w-full min-h-[305px] object-contain rounded border border-secondary-text cursor-pointer hover:bg-gray-50 transition">
                            <x-icons.camera class="w-10 h-10 text-secondary-text mb-2"/>

                            <x-form.input-file wire:model="form.photo" accept=".jpg,.jpeg,.png" class="hidden"/>
                            <span class="block w-full text-center text-secondary-text text-sm mt-2">Загрузить фото</span>
                        </label>
                    @endif

                    <span class="block w-full text-center text-secondary-text">Загрузить фото</span>
                </div>
            </x-form.form-field>
        </div>

        {{-- Прочее --}}
        <h2 class="font-semibold mt-6 mb-1">Прочее</h2>

        <x-form.form-field>
            <x-form.form-label tooltip="ID из интеграции Мегаплан">ID пользователя в Мегаплан</x-form.form-label>
            <x-form.input-text wire:model="form.megaplan_id" placeholder="1000272" />
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label>Важные уведомления</x-form.form-label>
            <x-form.toggle-switch wire:model="form.enable_important_notifications" />
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label>Обновление сервиса</x-form.form-label>
            <x-form.toggle-switch wire:model="form.enable_notifications" />
        </x-form.form-field>

        {{-- Кнопки --}}
        <div class="flex gap-3 mt-8">
            <x-button.button
                type="submit"
                variant="primary"
            >
                <x-slot:label>
                    {{ isset($form->id) ? 'Сохранить изменения' : 'Создать пользователя' }}
                </x-slot:label>
            </x-button.button>
            <x-button.button type="button" variant="secondary" onclick="window.history.back()">
                <x-slot:label>Отменить</x-slot:label>
            </x-button.button>
        </div>

    </div>
</x-form.form>
