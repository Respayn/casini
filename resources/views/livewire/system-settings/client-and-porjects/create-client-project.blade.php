<div>
    <x-menu.back-button/>
    <div class="flex flex-col gap-4 mt-4">
        <h1>Добавить клиенто-проект</h1>
        <x-form.form>
            <div class="flex flex-col gap-4">
                <h2>Основная информация</h2>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Статус клиенто-проекта</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
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
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Специалист</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                    >Помощники</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
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
                    <div>
                        <x-form.input-text wire:model=""></x-form.input-text>
                    </div>
                </x-form.form-field>
            </div>
            <div class="flex flex-col gap-4">
                <h2>Показатели по рынку</h2>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Регион продвижения</x-form.form-label>
                    <div>
                        <x-form.select wire:model=""></x-form.select>
                    </div>
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Тематика продвижения</x-form.form-label>
                    <div>
                        <x-form.select wire:model=""></x-form.select>
                    </div>
                </x-form.form-field>
            </div>
        </x-form.form>
    </div>
</div>
