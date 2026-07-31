<x-form.form :is-normalized="true" wire:submit.prevent="save" class="mt-4">
    <div class="flex flex-col gap-4">
        <h1 class="text-xl font-semibold">
            {{ isset($form->id) ? 'Редактировать пользователя' : 'Добавить пользователя' }}
        </h1>

        @if (session('password_updated'))
            <x-feedback.notice>{{ session('password_updated') }}</x-feedback.notice>
        @endif

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
                    :options="[['label'=>'Активен','value'=>true],['label'=>'Неактивен','value'=>false]]"
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

        @if($form->id)
            {{-- Пароль --}}
            <h2 class="font-semibold mt-6 mb-1">Пароль</h2>
            <div class="flex flex-col gap-4">
                <x-form.form-field>
                    <x-form.form-label>Текущий пароль</x-form.form-label>
                    <x-form.input-text
                        type="password"
                        wire:model.blur="form.current_password"
                        wire:blur="validatePasswordField('current_password')"
                        autocomplete="current-password"
                    />
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        tooltip="пароль должен состоять не менее чем из 6 символов и содержит латинские буквы и цифры"
                    >
                        Новый пароль
                    </x-form.form-label>
                    <x-form.input-text
                        type="password"
                        wire:model.blur="form.password"
                        wire:blur="validatePasswordField('password')"
                        autocomplete="new-password"
                    />
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label>Повторите новый пароль</x-form.form-label>
                    <x-form.input-text
                        type="password"
                        wire:model.blur="form.password_confirmation"
                        wire:blur="validatePasswordField('password_confirmation')"
                        autocomplete="new-password"
                    />
                </x-form.form-field>
            </div>
        @endif

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
                <div class="flex w-[305px] max-w-[305px] flex-col items-stretch gap-1">
                    @if($form->photo)
                        {{-- Превью до сохранения: клик по фото — выбрать другое --}}
                        <div
                            data-photo-wrap
                            class="relative w-full cursor-pointer"
                            x-data
                            @click="$refs.photoInput.click()"
                        >
                            <img src="{{ $form->photo->temporaryUrl() }}" alt="Фото профиля"
                                 class="w-full min-h-[200px] object-contain rounded border border-secondary-text" />
                            <input
                                x-ref="photoInput"
                                type="file"
                                accept=".jpg,.jpeg,.png"
                                class="sr-only"
                                wire:model="form.photo"
                                @click.stop
                            />
                        </div>
                        <x-button.button
                            type="button"
                            wire:click="deletePhoto"
                            x-on:click="typeof markDirty === 'function' && markDirty()"
                            icon="icons.delete"
                            class="w-full"
                            label="Удалить фото пользователя"
                        />
                    @elseif(!$form->delete_photo && $form->image_path)
                        {{-- Уже сохранённое фото: клик по фото — выбрать другое --}}
                        <div
                            data-photo-wrap
                            class="relative w-full cursor-pointer"
                            x-data
                            @click="$refs.photoInput.click()"
                        >
                            <img src="{{ Storage::url($form->image_path) }}" alt="Фото профиля"
                                 class="w-full min-h-[200px] object-contain rounded border border-secondary-text" />
                            <input
                                x-ref="photoInput"
                                type="file"
                                accept=".jpg,.jpeg,.png"
                                class="sr-only"
                                wire:model="form.photo"
                                @click.stop
                            />
                        </div>
                        <x-button.button
                            type="button"
                            wire:click="$set('form.delete_photo', true)"
                            x-on:click="typeof markDirty === 'function' && markDirty()"
                            icon="icons.delete"
                            class="w-full"
                            label="Удалить фото пользователя"
                        />
                    @else
                        <div
                            data-photo-wrap
                            class="relative flex w-full min-h-[305px] cursor-pointer flex-col items-center justify-center rounded border border-secondary-text object-contain transition hover:bg-gray-50"
                            x-data
                            @click="$refs.photoInput.click()"
                        >
                            <x-icons.camera class="mb-2 h-10 w-10 text-secondary-text"/>
                            <input
                                x-ref="photoInput"
                                type="file"
                                accept=".jpg,.jpeg,.png"
                                class="sr-only"
                                wire:model="form.photo"
                                @click.stop
                            />
                            <span class="mt-2 block w-full text-center text-sm text-secondary-text">Загрузить фото</span>
                        </div>
                    @endif

                    @error('form.photo')
                    <span class="text-warning-red text-[12px]">{{ $message }}</span>
                    @enderror
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

        @if ($showInlineActions ?? true)
            {{-- Кнопки --}}
            <div class="mt-8 flex gap-3">
                <x-button.button
                    type="submit"
                    variant="primary"
                    :disabled="$saveDisabled ?? false"
                >
                    <x-slot:label>
                        {{ isset($form->id) ? 'Сохранить изменения' : 'Создать пользователя' }}
                    </x-slot:label>
                </x-button.button>
                <x-button.button type="button" variant="secondary" onclick="window.history.back()">
                    <x-slot:label>Отменить</x-slot:label>
                </x-button.button>
            </div>
        @endif

    </div>
</x-form.form>
