<div>
    <x-menu.back-button/>
    <x-form.form :is-normalized="true" wire:submit.prevent="save">
        <div class="flex flex-col gap-4 mt-4 max-w-[950px]">
            <h1>Добавить клиенто-проект</h1>
                <div class="flex flex-col gap-4">
                    <h2>Основная информация</h2>
                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            required
                        >Статус клиенто-проекта</x-form.form-label>
                        <div>
                            <div class="ml-auto flex items-center justify-between w-[126px]">
                                <x-form.toggle-switch wire:model="clientProjectForm.status">
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
                            <x-form.input-text wire:model="clientProjectForm.name" placeholder="-"></x-form.input-text>
                        </div>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            required
                            tooltip="Чтобы клиент был в выпадающем списке нужно его добавить в Клиенты и клиенто-проекты"
                        >Выберите клиента</x-form.form-label>
                        <x-form.select wire:model="clientProjectForm.client" placeholder="-"></x-form.select>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            required
                            tooltip="Укажите основное зеркало сайта - как оно прописано в robots.txt"
                        >URL-адрес сайта</x-form.form-label>
                        <div>
                            <x-form.input-text wire:model="clientProjectForm.url"></x-form.input-text>
                        </div>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            tooltip="Менеджер указывается на уровне настроек клиента в Клиенты и клиенто-проекты"
                        >Менеджер</x-form.form-label>
                        <x-form.select wire:model="clientProjectForm.manager" placeholder="Выберите менеджера"/>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            required
                        >Специалист</x-form.form-label>
                        <x-form.select wire:model="clientProjectForm.specialist" placeholder="Выберите специалиста"/>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label class="self-baseline">
                            Помощники
                        </x-form.form-label>
                        <div class="flex flex-col gap-1 items-start">
                            <x-form.select wire:model="clientProjectForm.assistants" class="w-full" placeholder="Выберите помощника"/>
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
                        <x-form.select wire:model="clientProjectForm.kpi" placeholder="-"></x-form.select>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            required
                        >Отдел</x-form.form-label>
                        <x-form.select
                            wire:model.live="clientProjectForm.department"
                            :options="$departments->map(function ($item) {
                                return ['label' => $item->description, 'value' => $item->id];
                            })"
                        ></x-form.select>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            required
                            tooltip="Отметьте если проект “свой”, в этом случае колонка Акты в продукте Каналы будет заполнятся по итогам месяца автоматически на основе поля Чек-клиента"
                        >Тип клиенто-проекта</x-form.form-label>
                        <x-form.select wire:model="clientProjectForm.type" placeholder="-"></x-form.select>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                        >Свой проект</x-form.form-label>
                        <div class="flex items-center justify-end gap-3">
                            <label>Проект клиента</label>
                            <x-form.toggle-switch wire:model="clientProjectForm.ownerStatus"></x-form.toggle-switch>
                        </div>
                    </x-form.form-field>
                </div>
                <div class="flex flex-col gap-4 mt-4">
                    <h2>Показатели по рынку</h2>
                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            required
                            tooltip="На основе указанных данных мы будем подсказывать среднерыночные показатели при медиапланировании, данные храним в обезличенном виде"
                        >Регион продвижения</x-form.form-label>

                        <div class="flex flex-col gap-1 items-start">
                            <x-form.select wire:model="clientProjectForm.promotionRegions" placeholder="Выберите регион" class="w-full"/>
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
                            <x-form.select wire:model="clientProjectForm.promotionTopics" placeholder="Выберите тематику" class="w-full"/>
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
        </div>
        <div class="flex justify-between">
            <x-button.button
                variant="primary"
                type="submit"
            >
                <x-slot:label>
                    Сохранить клиенто-проект
                </x-slot:label>
            </x-button.button>
            <x-button.button
                wire:click="back"
            >
                <x-slot:label>
                    Отменить
                </x-slot:label>
            </x-button.button>
        </div>
    </x-form.form>
</div>
