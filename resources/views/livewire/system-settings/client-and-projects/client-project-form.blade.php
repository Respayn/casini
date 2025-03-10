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
                                <x-form.toggle-switch wire:model="clientProjectForm.is_active">
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
                        <x-form.select
                            wire:model="clientProjectForm.client"
                            placeholder="-"
                            :options="$clients->map(function ($item) {
                                return ['label' => $item->name, 'value' => $item->id];
                            })"
                        ></x-form.select>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            required
                            tooltip="Укажите основное зеркало сайта - как оно прописано в robots.txt"
                        >URL-адрес сайта</x-form.form-label>
                        <div>
                            <x-form.input-text wire:model="clientProjectForm.domain"></x-form.input-text>
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
{{--                            required--}}
                        >Специалист</x-form.form-label>
                        <x-form.select
                            wire:model="clientProjectForm.specialist"
                            placeholder="Выберите специалиста"
                        />
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
                        <x-form.select
                            wire:model="clientProjectForm.kpi"
                            :options="\App\Enums\Kpi::options()"
                            placeholder="-">
                        </x-form.select>
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
                        <x-form.select
                            wire:model="clientProjectForm.projectType"
                            placeholder="-"
                            :options="\App\Enums\ProjectType::options()"
                        ></x-form.select>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                        >Свой проект</x-form.form-label>
                        <div class="flex items-center justify-end gap-3">
                            <label>Проект клиента</label>
                            <x-form.toggle-switch wire:model="clientProjectForm.is_internal"></x-form.toggle-switch>
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
                            @foreach ($clientProjectForm->promotionRegions as $index => $region)
                                <div class="flex items-center gap-2 w-full">
                                    <x-form.select
                                        wire:model="clientProjectForm.promotionRegions.{{ $index }}"
                                        :options="$promotionRegions->map(function ($item) {
                                        return ['label' => $item->name, 'value' => $item->id];
                                    })"
                                        placeholder="Выберите регион"
                                        class="w-full flex-1"
                                    />
                                    @if(!empty($clientProjectForm->promotionRegions[$index]))
                                        <x-button.button
                                            type="button"
                                            wire:click="removeRegion({{ $index }})"
                                            variant="action"
                                        >
                                            <x-slot:label>Удалить</x-slot:label>
                                        </x-button.button>
                                    @endif
                                </div>
                            @endforeach

                            <x-button.button
                                type="button"
                                wire:click="addRegion"
                                variant="action"
                            >
                                <x-slot:label>Добавить регион</x-slot:label>
                            </x-button.button>
                        </div>
                    </x-form.form-field>
                    <x-form.form-field>
                        <x-form.form-label
                            class="self-baseline"
                            required
                            tooltip="..."
                        >Тематика продвижения</x-form.form-label>

                        <div class="flex flex-col gap-1 items-start">
                            @foreach ($clientProjectForm->promotionTopics as $index => $topic)
                                <div class="flex items-center gap-2 w-full">
                                    <x-form.select
                                        wire:model="clientProjectForm.promotionTopics.{{ $index }}"
                                        :options="$promotionTopics->map(function ($item) {
                        return ['label' => $item->topic, 'value' => $item->id];
                    })"
                                        placeholder="Выберите тематику"
                                        class="w-full flex-1"
                                    />
                                    @if(!empty($clientProjectForm->promotionTopics[$index]))
                                        <x-button.button
                                            type="button"
                                            wire:click="removeTopic({{ $index }})"
                                            variant="action"
                                        >
                                            <x-slot:label>Удалить</x-slot:label>
                                        </x-button.button>
                                    @endif
                                </div>
                            @endforeach

                            <x-button.button
                                type="button"
                                wire:click="addTopic"
                                variant="action"
                            >
                                <x-slot:label>Добавить тематику</x-slot:label>
                            </x-button.button>
                        </div>
                    </x-form.form-field>
                </div>
                <div class="flex flex-col gap-4 mt-4">
                    <!-- Бонусы и гарантии -->
                    <h2>Бонусы и гарантии</h2>

                    <x-form.form-field>
                        <x-form.form-label tooltip="Если за выполнение плана в договоре с клиентом предусмотрен бонус и/или прописаны гарантии - задайте логику расчета бонуса и/или гарантии">
                            В договоре предусмотрены бонусы и/или гарантии
                        </x-form.form-label>
                        <div class="flex items-center justify-end gap-3">
                            <label>Да</label>
                            <x-form.toggle-switch wire:model.live="clientProjectForm.bonusGuaranteeForm.bonuses_enabled" />
                        </div>
                    </x-form.form-field>

                    @if ($clientProjectForm->bonusGuaranteeForm->bonuses_enabled)
                        <!-- Расчет в % от суммы чека клиента -->
                        <x-form.form-field>
                            <x-form.form-label>
                                Бонус и/или гарантия рассчитывается в % от суммы чека клиента
                            </x-form.form-label>
                            <div class="flex items-center justify-end gap-3">
                                <label>Да</label>
                                <x-form.toggle-switch wire:model.live="clientProjectForm.bonusGuaranteeForm.calculate_in_percentage" />
                            </div>
                        </x-form.form-field>

                        <!-- С какого месяца начинать считать бонусы и/или гарантию -->
                        <x-form.form-field>
                            <x-form.form-label required>
                                С какого месяца начинать считать бонусы и/или гарантию?
                            </x-form.form-label>
                            <x-form.select
                                wire:model="clientProjectForm.bonusGuaranteeForm.start_month"
                                :options="[
                                ['label' => 'Начиная с 1-го месяца работы', 'value' => 1],
                                ['label' => 'Начиная со 2-го месяца работы', 'value' => 2],
                                ['label' => 'Начиная с 3-го месяца работы', 'value' => 3],
                            ]"
                                placeholder="Выберите вариант"
                            />
                        </x-form.form-field>

                        <!-- Чек клиента -->
                        <x-form.form-field>
                            <x-form.form-label tooltip="Сколько клиент платит за ведение клиенто-проекта.">
                                Чек клиента
                            </x-form.form-label>
                            <x-form.input-text
                                type="number"
                                wire:model="clientProjectForm.bonusGuaranteeForm.client_payment"
                                placeholder="Сумма в рублях"
                                suffix="₽"
                            />
                        </x-form.form-field>

                        <div class="flex flex-col gap-4 mt-4">
                            <!-- Логика расчета бонуса и/или гарантии -->
                            <h2>Задайте логику расчета бонуса и/или гарантии</h2>

                            <x-form.form-field>
                                <x-form.form-label required>Выполнение плана в % (включительно)</x-form.form-label>

                                {{-- Таблица с логикой расчета --}}
                                <div class="grid grid-cols-4 grid-cols-[30px_auto_30px_auto] max-w-[489px] text-[14px] items-center gap-x-1 gap-y-2">
                                    <div></div>
                                    <div class="max-w-xs text-secondary-text">Выполнение плана в % (включительно)</div>
                                    <div></div>
                                    <div class="max-w-xs text-secondary-text">Бонус и/или гарантия в % от чека клиента</div>

                                    @foreach ($clientProjectForm->bonusGuaranteeForm->intervals as $index => $interval)
                                        <div class="text-center text-secondary-text">От</div>
                                        <div class="flex items-center gap-2">
                                            <x-form.input-text
                                                type="number"
                                                wire:model="clientProjectForm.bonusGuaranteeForm.intervals.{{ $index }}.from_percentage"
                                                placeholder="От"
                                                suffix="%"
                                            />
                                            <span class="text-secondary-text">
                                                До
                                            </span>
                                            <x-form.input-text
                                                type="number"
                                                wire:model="clientProjectForm.bonusGuaranteeForm.intervals.{{ $index }}.to_percentage"
                                                placeholder="До"
                                                suffix="%"
                                            />
                                        </div>
                                        <div class="text-center text-secondary-text">-</div>
                                        <div class="flex items-center gap-2">
                                            <x-form.input-text
                                                type="number"
                                                wire:model="clientProjectForm.bonusGuaranteeForm.intervals.{{ $index }}.bonus_amount"
                                                placeholder="Сумма в рублях"
                                            />
                                            <x-button.button
                                                type="button"
                                                wire:click.prevent="removeInterval({{ $index }})"
                                                variant="action"
                                            >
                                                <x-slot:label>Удалить</x-slot:label>
                                            </x-button.button>
                                        </div>
                                        <div class="col-span-4 flex items-center justify-center">
                                            <x-button.button
                                                type="button"
                                                wire:click.prevent="addInterval"
                                                variant="action"
                                            >
                                                <x-slot:label>Добавить диапазон</x-slot:label>
                                            </x-button.button>
                                        </div>
                                    @endforeach
                                </div>
                            </x-form.form-field>
                        </div>
                    @endif
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
