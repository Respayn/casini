<div>
    <x-menu.back-button />
    <x-form.form
        :is-normalized="true"
        wire:submit.prevent="save"
    >
        <div class="mt-4 flex max-w-[950px] flex-col gap-4">
            <h1>Добавить клиенто-проект</h1>
            <div class="flex flex-col gap-4">
                <h2>Основная информация</h2>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Статус клиенто-проекта</x-form.form-label>
                    <div>
                        <div class="ml-auto flex w-[126px] items-center justify-between">
                            <x-form.toggle-switch wire:model="clientProjectForm.isActive">
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
                        <x-form.input-text
                            wire:model="clientProjectForm.name"
                            placeholder="-"
                        ></x-form.input-text>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="Чтобы клиент был в выпадающем списке нужно его добавить в Клиенты и клиенто-проекты"
                    >Выберите клиента</x-form.form-label>
                    <div>
                        <x-form.select
                            wire:model="clientProjectForm.client"
                            placeholder="-"
                            :options="$clients->map(function ($item) {
                                return ['label' => $item->name, 'value' => $item->id];
                            })"
                        ></x-form.select>
                    </div>
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
                    <div>
                        <x-form.select
                            :options="$this->managers->map(function ($item) {
                                return ['label' => $item->first_name . ' ' . $item->last_name, 'value' => $item->id];
                            })"
                            wire:model="clientProjectForm.manager"
                            placeholder="Выберите менеджера"
                            disabled
                        />
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label class="self-baseline">Специалист</x-form.form-label>
                    <div>
                        <x-form.select
                            :options="$this->specialists->map(function ($item) {
                                return ['label' => $item->first_name . ' ' . $item->last_name, 'value' => $item->id];
                            })"
                            wire:model="clientProjectForm.specialist"
                            placeholder="Выберите специалиста"
                        />
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label class="self-baseline">
                        Помощники
                    </x-form.form-label>
                    <div class="flex flex-col gap-1">
                        <x-form.select
                            class="w-full"
                            wire:model="clientProjectForm.assistants"
                            placeholder="Выберите помощника"
                        />
                        <x-button.button
                            class="self-start"
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
                        <x-form.select
                            wire:model.live="clientProjectForm.kpi"
                            :options="\Src\Shared\ValueObjects\Kpi::options()"
                            placeholder="-"
                        >
                        </x-form.select>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="Отметьте если проект “свой”, в этом случае колонка Акты в продукте Каналы будет заполнятся по итогам месяца автоматически на основе поля Чек-клиента"
                    >Тип клиенто-проекта</x-form.form-label>
                    <div>
                        <x-form.select
                            wire:model.live="clientProjectForm.projectType"
                            placeholder="-"
                            :options="\Src\Shared\ValueObjects\ProjectType::options()"
                        ></x-form.select>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label class="self-baseline">Свой проект</x-form.form-label>
                    <div class="flex items-center justify-end gap-3">
                        <label>Проект клиента</label>
                        <x-form.toggle-switch wire:model="clientProjectForm.isInternal"></x-form.toggle-switch>
                    </div>
                </x-form.form-field>
            </div>
            <div class="mt-4 flex flex-col gap-4">
                <h2>Показатели по рынку</h2>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="На основе указанных данных мы будем подсказывать среднерыночные показатели при медиапланировании, данные храним в обезличенном виде"
                    >Регион продвижения</x-form.form-label>

                    <div class="flex flex-col items-start gap-1">
                        @foreach ($clientProjectForm->promotionRegions as $index => $region)
                            <div class="flex w-full items-center gap-2">
                                <x-form.select
                                    class="w-full flex-1"
                                    wire:model="clientProjectForm.promotionRegions.{{ $index }}"
                                    :options="$promotionRegions->map(function ($item) {
                                        return ['label' => $item->name, 'value' => $item->id];
                                    })"
                                    placeholder="Выберите регион"
                                />
                                @if (!empty($clientProjectForm->promotionRegions[$index]))
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
                    >Тематика продвижения</x-form.form-label>

                    <div class="flex flex-col items-start gap-1">
                        @foreach ($clientProjectForm->promotionTopics as $index => $topic)
                            <div class="flex w-full items-center gap-2">
                                <x-form.select
                                    class="w-full flex-1"
                                    wire:model="clientProjectForm.promotionTopics.{{ $index }}"
                                    :options="$promotionTopics->map(function ($item) {
                                        return ['label' => $item->topic, 'value' => $item->id];
                                    })"
                                    placeholder="Выберите тематику"
                                />
                                @if (!empty($clientProjectForm->promotionTopics[$index]))
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
            <div class="mt-4 flex flex-col gap-4">
                <!-- Бонусы и гарантии -->
                <h2>Бонусы и гарантии</h2>

                <x-form.form-field>
                    <x-form.form-label
                        tooltip="Если за выполнение плана в договоре с клиентом предусмотрен бонус и/или прописаны гарантии - задайте логику расчета бонуса и/или гарантии"
                    >
                        В договоре предусмотрены бонусы и/или гарантии
                    </x-form.form-label>
                    <div class="flex items-center justify-end gap-3">
                        <label>Да</label>
                        <x-form.toggle-switch wire:model.live="bonusGuaranteeForm.bonusesEnabled" />
                    </div>
                </x-form.form-field>

                @if ($bonusGuaranteeForm->bonusesEnabled)
                    <!-- Расчет в % от суммы чека клиента -->
                    <x-form.form-field>
                        <x-form.form-label>
                            Бонус и/или гарантия рассчитывается в % от суммы чека клиента
                        </x-form.form-label>
                        <div class="flex items-center justify-end gap-3">
                            <label>Да</label>
                            <x-form.toggle-switch wire:model.live="bonusGuaranteeForm.calculateInPercentage" />
                        </div>
                    </x-form.form-field>

                    <!-- С какого месяца начинать считать бонусы и/или гарантию -->
                    <x-form.form-field>
                        <x-form.form-label required>
                            С какого месяца начинать считать бонусы и/или гарантию?
                        </x-form.form-label>
                        <div>
                            <x-form.select
                                wire:model="bonusGuaranteeForm.startMonth"
                                :options="[
                                    ['label' => 'Начиная с 1-го месяца работы', 'value' => 1],
                                    ['label' => 'Начиная со 2-го месяца работы', 'value' => 2],
                                    ['label' => 'Начиная с 3-го месяца работы', 'value' => 3],
                                ]"
                                placeholder="Выберите вариант"
                            />
                        </div>
                    </x-form.form-field>

                    <!-- Чек клиента -->
                    <x-form.form-field>
                        <x-form.form-label tooltip="Сколько клиент платит за ведение клиенто-проекта.">
                            Чек клиента
                        </x-form.form-label>
                        <x-form.input-text
                            type="number"
                            wire:model="bonusGuaranteeForm.clientPayment"
                            placeholder="Сумма в рублях"
                            suffix="₽"
                        />
                    </x-form.form-field>

                    <div class="mt-4 flex flex-col gap-4">
                        <!-- Логика расчета бонуса и/или гарантии -->
                        <h2>Задайте логику расчета бонуса и/или гарантии</h2>

                        <x-form.form-field>
                            <x-form.form-label required>Выполнение плана в % (включительно)</x-form.form-label>

                            {{-- Таблица с логикой расчета --}}
                            <div
                                class="grid max-w-[489px] grid-cols-4 grid-cols-[30px_auto_30px_auto] items-center gap-x-1 gap-y-2 text-[14px]">
                                <div></div>
                                <div class="text-secondary-text max-w-xs">Выполнение плана в % (включительно)</div>
                                <div></div>
                                <div class="text-secondary-text max-w-xs">Бонус и/или гарантия в % от чека клиента</div>

                                @foreach ($bonusGuaranteeForm->intervals as $index => $interval)
                                    <div class="text-secondary-text text-center">От</div>
                                    <div class="flex items-center gap-2">
                                        <x-form.input-text
                                            type="number"
                                            wire:model="bonusGuaranteeForm.intervals.{{ $index }}.fromPercentage"
                                            placeholder="От"
                                            suffix="%"
                                        />
                                        <span class="text-secondary-text">
                                            До
                                        </span>
                                        <x-form.input-text
                                            type="number"
                                            wire:model="bonusGuaranteeForm.intervals.{{ $index }}.toPercentage"
                                            placeholder="До"
                                            suffix="%"
                                        />
                                    </div>
                                    <div class="text-secondary-text text-center">-</div>
                                    <div class="flex items-center gap-2">
                                        @if ($bonusGuaranteeForm->calculateInPercentage)
                                            <x-form.input-text
                                                type="number"
                                                wire:model="bonusGuaranteeForm.intervals.{{ $index }}.bonusPercentage"
                                                placeholder="Сумма в процентах"
                                                suffix="%"
                                            />
                                        @else
                                            <x-form.input-text
                                                type="number"
                                                wire:model="bonusGuaranteeForm.intervals.{{ $index }}.bonusAmount"
                                                placeholder="Сумма в рублях"
                                                suffix="₽"
                                            />
                                        @endif

                                        <x-button.button
                                            type="button"
                                            wire:click.prevent="removeInterval({{ $index }})"
                                            variant="action"
                                        >
                                            <x-slot:label>Удалить</x-slot:label>
                                        </x-button.button>
                                    </div>
                                @endforeach
                                <div class="col-span-4 flex items-center justify-center">
                                    <x-button.button
                                        type="button"
                                        wire:click.prevent="addInterval"
                                        variant="action"
                                    >
                                        <x-slot:label>Добавить диапазон</x-slot:label>
                                    </x-button.button>
                                </div>
                            </div>
                        </x-form.form-field>
                    </div>
                @endif
            </div>

            <div class="mt-4 flex flex-col gap-4">
                <h1>Интеграции</h1>
                <div class="text-caption-text">Подключите сервисы для автоматической работы с рекламными инструментами,
                    финансами и аналитикой вашего клиенто-проекта</div>

                <div class="flex gap-2.5">
                    <x-project-form.integration-settings-card
                        title="Инструменты"
                        description="Подключите рекламные инструменты, например Яндекс Директ"
                        :configured-integrations="$this->configuredToolsIntegrations"
                        modal-trigger-name="tools-integrations-modal"
                    />
                    <x-project-form.integration-settings-card
                        title="Деньги"
                        description="Настройте интеграцию для получения информации по деньгам и актам в канале"
                        :configured-integrations="$this->configuredMoneyIntegrations"
                        modal-trigger-name="money-integrations-modal"
                    />
                    <x-project-form.integration-settings-card
                        title="Аналитика"
                        description="Интеграции, с помощью которых будете получать количество визитов, конверсий или позиции"
                        :configured-integrations="$this->configuredAnalyticsIntegrations"
                        modal-trigger-name="analytics-integrations-modal"
                    />
                    {{-- {{ $this->configuredAnalyticsIntegrations }} --}}
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-4">
                <h1>Настройка параметров</h1>

                <x-form.form-field>
                    <x-form.form-label class="font-bold">Фактические параметры</x-form.form-label>
                    <x-form.form-label class="font-bold">Схема расчета параметра</x-form.form-label>
                </x-form.form-field>

                @if (empty($clientProjectForm->projectType) || empty($clientProjectForm->kpi))
                    {{-- KPI: Трафик; Тип канала: Контекст --}}
                    <span class="text-default-button-disabled flex items-center justify-center text-[18px] italic">
                        Выберите KPI и Тип клиенто-проекта
                    </span>
                @elseif(
                    $clientProjectForm->kpi === \Src\Shared\ValueObjects\Kpi::TRAFFIC->value &&
                        $clientProjectForm->projectType === \Src\Shared\ValueObjects\ProjectType::CONTEXT_AD->value)
                    {{-- KPI: Трафик; Тип канала: Контекст --}}
                    <x-form.form-field>
                        <x-form.form-label>CPС</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Яндекс Директ, расходы / Яндекс Директ, клики'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                    <x-form.form-field>
                        <x-form.form-label>Рекламный бюджет</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Яндекс Директ, расходы'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                    <x-form.form-field>
                        <x-form.form-label class="font-bold">Визитов</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Яндекс Директ, клики'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                @elseif(
                    $clientProjectForm->kpi === \Src\Shared\ValueObjects\Kpi::LEADS->value &&
                        $clientProjectForm->projectType === \Src\Shared\ValueObjects\ProjectType::CONTEXT_AD->value)
                    {{-- KPI: Лиды; Тип канала: Контекст --}}
                    <x-form.form-field>
                        <x-form.form-label class="font-bold">CPL</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Яндекс Директ, расходы / (Calibri, ЕЖЛ + Яндекс Метрика, достижение целей из отчета UTM-метки)'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                    <x-form.form-field>
                        <x-form.form-label class="font-bold">Рекламный бюджет</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Яндекс Директ, расходы'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                    <x-form.form-field>
                        <x-form.form-label class="font-bold">Лиды</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Яндекс Метрика, достижение целей из отчета UTM-метки, ЕЖЛ'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                @elseif(
                    $clientProjectForm->kpi === \Src\Shared\ValueObjects\Kpi::POSITIONS->value &&
                        $clientProjectForm->projectType === \Src\Shared\ValueObjects\ProjectType::SEO_PROMOTION->value)
                    {{-- KPI: Позиции; Тип канала: SEO --}}
                    <x-form.form-field>
                        <x-form.form-label class="font-bold">% позиций в топ 10</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Yandex Search API'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                    <x-form.form-field>
                        <x-form.form-label class="font-bold">Конверсии</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Яндекс Метрика, достижение целей из отчета Поисковые системы'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                @elseif(
                    $clientProjectForm->kpi === \Src\Shared\ValueObjects\Kpi::TRAFFIC->value &&
                        $clientProjectForm->projectType === \Src\Shared\ValueObjects\ProjectType::SEO_PROMOTION->value)
                    {{-- KPI: Трафик; Тип канала: SEO --}}
                    <x-form.form-field>
                        <x-form.form-label class="font-bold">Объем визитов</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Яндекс Метрика, переходы из отчета Поисковые системы'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                    <x-form.form-field>
                        <x-form.form-label class="font-bold">Конверсии</x-form.form-label>
                        <div class="w-full max-w-[489px]">
                            <span class="text-[14px]">
                                Данные из интеграций поступают с учетом заданных настроек
                            </span>
                            <x-form.input-text
                                :value="'Яндекс Метрика, достижение целей из отчета Поисковые системы'"
                                disabled
                            />
                        </div>
                    </x-form.form-field>
                @endif
            </div>

            <div class="mt-4 flex flex-col gap-4">
                <h1>Пересбор статистики клиенто-проекта</h1>
                <x-form.form-field>
                    <x-form.form-label
                        class="font-bold"
                        tooltip="Укажите период за который нужно обновить отчеты с учетом обновленных: целей, счетчиков Метрики, выбранных UTM-меток, условий, интеграций"
                    >Выберите период</x-form.form-label>
                    <div class="flex flex-col gap-2">
                        <div class="flex flex-row items-center gap-2">
                            <x-form.date-picker />
                            <span>-</span>
                            <x-form.date-picker />
                        </div>
                        <x-button.button
                            class="w-full"
                            variant="implicit-action"
                            label="Пересобрать статистику"
                        />
                        <div class="ml-auto mt-5 rounded-full bg-red-100 px-3 py-1">
                            Не начато
                        </div>
                    </div>
                </x-form.form-field>
            </div>

            @if ($clientProjectForm->projectType === \Src\Shared\ValueObjects\ProjectType::CONTEXT_AD->value)
                <div class="mt-4 flex flex-col gap-4">
                    <h1>Генерация клиентских отчетов</h1>

                    {{-- Таблица с логикой расчета --}}
                    <div
                        class="grid w-full grid-cols-8 grid-cols-[auto_30px_auto_30px_auto_30px_auto_100px] items-center gap-x-1 gap-y-2 text-[14px]">
                        <div class="text-secondary-text max-w-xs">Задайте условия подмены UTM-меток в отчетах</div>
                        <div></div>
                        <div class="text-secondary-text max-w-xs">Выберите UTM-метку</div>
                        <div></div>
                        <div class="text-secondary-text max-w-xs">Введите значение подменяемой UTM-метки</div>
                        <div></div>
                        <div class="text-secondary-text max-w-xs">Введите значение, которое отобразится в отчете</div>

                        <?php /** @var \App\Livewire\Forms\SystemSettings\ClientAndProjects\ProjectUtmMappingForm $utmMappingForm */ ?>
                        @foreach ($utmMappingForm->utmMappings as $index => $utmMappingItem)
                            <div class="col-start-3 flex items-center gap-2">
                                <x-form.input-text
                                    placeholder="Выберите UTM-метку"
                                    wire:model.defer="utmMappingForm.utmMappings.{{ $index }}.utmType"
                                    disabled
                                />
                            </div>
                            <div class="text-secondary-text text-center">-</div>
                            <div class="flex items-center gap-2">
                                <x-form.input-text
                                    placeholder="Введите значение"
                                    wire:model.defer="utmMappingForm.utmMappings.{{ $index }}.utmValue"
                                />
                            </div>
                            <div class="text-secondary-text text-center">=</div>
                            <x-form.input-text
                                placeholder="Значение в отчете"
                                wire:model.defer="utmMappingForm.utmMappings.{{ $index }}.replacementValue"
                            />
                            <x-button.button
                                type="button"
                                wire:click.prevent="removeMapping({{ $index }})"
                                variant="action"
                            >
                                <x-slot:label>Удалить</x-slot:label>
                            </x-button.button>
                        @endforeach
                        <div class="col-start-1 flex items-center justify-center">
                            <x-button.button
                                type="button"
                                wire:click.prevent="addMapping"
                                variant="action"
                            >
                                <x-slot:label>Добавить условие</x-slot:label>
                            </x-button.button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="mt-4 flex justify-between">
            <x-button.button
                type="submit"
                variant="primary"
            >
                <x-slot:label>
                    Сохранить клиенто-проект
                </x-slot:label>
            </x-button.button>
            <x-button.button
                href="javascript:void(0);"
                onclick="window.history.back()"
            >
                <x-slot:label>
                    Отменить
                </x-slot:label>
            </x-button.button>
        </div>
    </x-form.form>

    <x-project-form.integration-list-modal
        name="tools-integrations-modal"
        title="Инструменты"
        :integrations="$this->toolsIntegrations"
    />

    <x-project-form.integration-list-modal
        name="money-integrations-modal"
        title="Деньги"
        :integrations="$this->moneyIntegrations"
    />

    <x-project-form.integration-list-modal
        name="analytics-integrations-modal"
        title="Аналитика"
        :integrations="$this->analyticsIntegrations"
    />

    <x-project-form.integration-settings-modal :project-integration="$selectedIntegration" />
</div>
