<div>
    <x-menu.back-button/>
    <div class="flex flex-col gap-4 mt-4 max-w-[950px]">
        <h1>Добавить клиенто-проект</h1>
        <x-form.form :is-normalized="true">
            <div class="flex flex-col gap-4">
                <h2>Основная информация</h2>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Статус клиенто-проекта</x-form.form-label>
                    <div>
                        <div class="ml-auto flex items-center justify-between w-[126px]">
                            <x-form.toggle-switch wire:model="">
                            </x-form.toggle-switch>
                            <label>
                                Активен
                            </label>
                        </div>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Название клиенто-проекта</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Выберите клиента</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >URL-адрес сайта</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                    >Менеджер</x-form.form-label>
                    <x-form.select wire:model="" placeholder="Выберите менеджера"/>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Специалист</x-form.form-label>
                    <x-form.select wire:model="" placeholder="Выберите специалиста"/>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label class="self-baseline">
                        Помощники
                    </x-form.form-label>
                    <div class="flex flex-col gap-1 items-start">
                        <x-form.select wire:model="" class="w-full" placeholder="Выберите помощника"/>
                        <x-button.button
                            variant="action"
                        >
                            <x-slot:label>
                                Добавить помощника
                            </x-slot:label>
                        </x-button.button>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >KPI</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Отдел</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Тип клиенто-проекта</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                    >Свой проект</x-form.form-label>
                    <div class="flex items-center justify-end gap-3">
                        <label>Проект клиента</label>
                        <x-form.toggle-switch wire:model=""></x-form.toggle-switch>
                    </div>
                </x-form.form-field>
            </div>
            <div class="flex flex-col gap-4 mt-4">
                <h2>Показатели по рынку</h2>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Регион продвижения</x-form.form-label>

                    <div class="flex flex-col gap-1 items-start">
                        <x-form.select wire:model="" placeholder="Выберите регион" class="w-full"/>
                        <x-button.button
                            variant="action"
                        >
                            <x-slot:label>
                                Добавить регион
                            </x-slot:label>
                        </x-button.button>
                    </div>
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Тематика продвижения</x-form.form-label>
                    <div class="flex flex-col gap-1 items-start">
                        <x-form.select wire:model="" placeholder="Выберите тематику" class="w-full"/>
                        <x-button.button
                            variant="action"
                        >
                            <x-slot:label>
                                Добавить тематику
                            </x-slot:label>
                        </x-button.button>
                    </div>
                </x-form.form-field>
            </div>
        </x-form.form>
    </div>
    <div class="flex justify-between">
        <x-button.button
            variant="primary"
            wire:click="updateNotification"
        >
            <x-slot:label>
                Сохранить клиенто-проект
            </x-slot:label>
        </x-button.button>
        <x-button.button
            wire:click="updateNotification"
        >
            <x-slot:label>
                Отменить
            </x-slot:label>
        </x-button.button>
    </div>
</div>
