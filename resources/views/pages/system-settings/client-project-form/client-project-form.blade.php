@php
    $canEdit = $this->canEditClientsAndProjects;
@endphp

<div
    data-casini-client-project-form
    x-data="{
        hasPendingChanges: @js($startWithPendingChanges),
        successMessage: @js($startWithSuccessMessage) ? 'Изменения сохранены' : null,
        canEditPage: @js($canEdit),
        markDirty() {
            if (this.canEditPage) {
                this.hasPendingChanges = true;
                this.successMessage = null;
            }
        }
    }"
    x-on:input.capture="markDirty()"
    x-on:change.capture="markDirty()"
    x-on:client-project-mark-dirty.window="markDirty()"
>
    <x-menu.back-button />

    <div
        x-show="successMessage"
        x-cloak
        class="border-primary mt-4 mb-4 max-w-[950px] break-words rounded-lg border bg-blue-50 p-4 text-sm text-primary-text"
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
                <h1>Добавить клиенто-проект</h1>
            <div class="flex flex-col gap-4">
                <h2>Основная информация</h2>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Статус клиенто-проекта</x-form.form-label>
                    <div>
                        <div class="flex items-center justify-end gap-3">
                            <label>
                                {{ $clientProjectForm->isActive ? 'Активен' : 'Неактивный' }}
                            </label>
                            <x-permissions.field-guard :enabled="$canEdit" :fill="false">
                                <x-form.toggle-switch wire:model.live="clientProjectForm.isActive" :disabled="! $canEdit">
                                </x-form.toggle-switch>
                            </x-permissions.field-guard>
                        </div>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label>Дата создания клиенто-проекта</x-form.form-label>
                    <div>
                        <x-form.input-text
                            wire:model="clientProjectForm.createdAt"
                            placeholder="дд.мм.гггг"
                            disabled
                        />
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label>Дата архивации клиенто-проекта</x-form.form-label>
                    <div wire:key="archived-at-{{ $clientProjectForm->isActive ? 'active' : 'archived-'.$clientProjectForm->archivedAt }}">
                        <x-form.input-text
                            :value="$clientProjectForm->isActive ? '' : $clientProjectForm->archivedAt"
                            placeholder="дд.мм.гггг"
                            disabled
                        />
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Название клиенто-проекта</x-form.form-label>
                    <div>
                        <x-permissions.field-guard :enabled="$canEdit">
                            <x-form.input-text
                                wire:model="clientProjectForm.name"
                                placeholder="-"
                                :disabled="! $canEdit"
                            ></x-form.input-text>
                        </x-permissions.field-guard>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="Чтобы клиент был в выпадающем списке нужно его добавить в Клиенты и клиенто-проекты"
                    >Выберите клиента</x-form.form-label>
                    <div>
                        <x-permissions.field-guard :enabled="$canEdit">
                            <x-form.select
                                wire:model="clientProjectForm.client"
                                placeholder="-"
                                :options="$clients->map(function ($item) {
                                    return ['label' => $item->name, 'value' => $item->id];
                                })"
                                :disabled="! $canEdit"
                            ></x-form.select>
                        </x-permissions.field-guard>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="Укажите основное зеркало сайта - как оно прописано в robots.txt"
                    >URL-адрес сайта</x-form.form-label>
                    <div>
                        <x-permissions.field-guard :enabled="$canEdit">
                            <x-form.input-url
                                wire:model="clientProjectForm.domain"
                                placeholder="example.com"
                                :disabled="! $canEdit"
                            />
                        </x-permissions.field-guard>
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
                        <x-permissions.field-guard :enabled="$canEdit">
                            <x-form.select
                                :options="$this->specialistSelectOptions"
                                wire:model="clientProjectForm.specialist"
                                placeholder="Выберите специалиста"
                                :disabled="! $canEdit"
                            />
                        </x-permissions.field-guard>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label class="self-baseline">
                        Помощники
                    </x-form.form-label>
                    <div class="flex flex-col gap-1">
                        @foreach ($clientProjectForm->assistants as $index => $assistant)
                            <div
                                class="flex w-full items-center gap-2"
                                wire:key="assistant-row-{{ $index }}"
                            >
                                <x-permissions.field-guard :enabled="$canEdit">
                                    <x-form.select
                                        class="w-full flex-1"
                                        :options="$this->specialistSelectOptions"
                                        wire:model="clientProjectForm.assistants.{{ $index }}"
                                        placeholder="Выберите помощника"
                                        :disabled="! $canEdit"
                                    />
                                </x-permissions.field-guard>
                                @if ($canEdit && $index > 0)
                                    <x-button.button
                                        type="button"
                                        wire:click="removeAssistant({{ $index }})"
                                        icon="icons.delete"
                                        title="Удалить"
                                    />
                                @endif
                            </div>
                        @endforeach
                        <x-permissions.field-guard :enabled="$canEdit">
                            <x-button.button
                                class="self-start"
                                type="button"
                                variant="action"
                                wire:click.prevent="addAssistant"
                                :disabled="! $canEdit"
                            >
                                <x-slot:label>
                                    Добавить помощника
                                </x-slot:label>
                            </x-button.button>
                        </x-permissions.field-guard>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >KPI</x-form.form-label>
                    <div>
                        <x-permissions.field-guard :enabled="$canEdit">
                            <x-form.select
                                wire:model.live="clientProjectForm.kpi"
                                :options="\Src\Domain\ValueObjects\Kpi::options()"
                                placeholder="-"
                                :disabled="! $canEdit"
                            >
                            </x-form.select>
                        </x-permissions.field-guard>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="Отметьте если проект «свой», в этом случае колонка Акты в продукте Каналы будет заполнятся по итогам месяца автоматически на основе поля Чек-клиента"
                    >Тип клиенто-проекта</x-form.form-label>
                    <div>
                        <x-permissions.field-guard :enabled="$canEdit">
                            <x-form.select
                                wire:model.live="clientProjectForm.projectType"
                                placeholder="-"
                                :options="\Src\Domain\ValueObjects\ProjectType::options()"
                                :disabled="! $canEdit"
                            ></x-form.select>
                        </x-permissions.field-guard>
                    </div>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label class="self-baseline">Свой проект</x-form.form-label>
                    <div class="flex items-center justify-end gap-3">
                        <label>{{ $clientProjectForm->isInternal ? 'Свой проект' : 'Проект клиента' }}</label>
                        <x-permissions.field-guard :enabled="$canEdit" :fill="false">
                            <x-form.toggle-switch wire:model.live="clientProjectForm.isInternal" :disabled="! $canEdit"></x-form.toggle-switch>
                        </x-permissions.field-guard>
                    </div>
                </x-form.form-field>
            </div>
            {{-- Временно скрыто: показатели по рынку (регион / тематика) --}}
            @if (false)
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
                                <x-permissions.field-guard :enabled="$canEdit">
                                    <x-form.select
                                        class="w-full flex-1"
                                        wire:model="clientProjectForm.promotionRegions.{{ $index }}"
                                        :options="$promotionRegions->map(function ($item) {
                                            return ['label' => $item->name, 'value' => $item->id];
                                        })"
                                        placeholder="Выберите регион"
                                        :disabled="! $canEdit"
                                    />
                                </x-permissions.field-guard>
                                @if (!empty($clientProjectForm->promotionRegions[$index]) && $canEdit)
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

                        @if ($canEdit)
                            <x-button.button
                                type="button"
                                wire:click="addRegion"
                                variant="action"
                            >
                                <x-slot:label>Добавить регион</x-slot:label>
                            </x-button.button>
                        @endif
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
                                <x-permissions.field-guard :enabled="$canEdit">
                                    <x-form.select
                                        class="w-full flex-1"
                                        wire:model="clientProjectForm.promotionTopics.{{ $index }}"
                                        :options="$promotionTopics->map(function ($item) {
                                            return ['label' => $item->topic, 'value' => $item->id];
                                        })"
                                        placeholder="Выберите тематику"
                                        :disabled="! $canEdit"
                                    />
                                </x-permissions.field-guard>
                                @if (!empty($clientProjectForm->promotionTopics[$index]) && $canEdit)
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

                        @if ($canEdit)
                            <x-button.button
                                type="button"
                                wire:click="addTopic"
                                variant="action"
                            >
                                <x-slot:label>Добавить тематику</x-slot:label>
                            </x-button.button>
                        @endif
                    </div>
                </x-form.form-field>
            </div>
            @endif
            <div class="mt-4 flex flex-col gap-4">
                <!-- Бонусы и гарантии -->
                <h2>Бонусы и гарантии</h2>

                <!-- Чек клиента -->
                <x-form.form-field>
                    <x-form.form-label tooltip="Сколько клиент платит за ведение клиенто-проекта.">
                        Чек клиента
                    </x-form.form-label>
                    <x-permissions.field-guard :enabled="$canEdit">
                        <x-form.input-text
                            type="number"
                            wire:model="bonusGuaranteeForm.clientPayment"
                            placeholder="Сумма в рублях"
                            suffix="₽"
                            :disabled="! $canEdit"
                        />
                    </x-permissions.field-guard>
                </x-form.form-field>

                <x-form.form-field>
                    <x-form.form-label
                        tooltip="Если за выполнение плана в договоре с клиентом предусмотрен бонус и/или прописаны гарантии - задайте логику расчета бонуса и/или гарантии"
                    >
                        В договоре предусмотрены бонусы и/или гарантии
                    </x-form.form-label>
                    <div class="flex items-center justify-end gap-3">
                        <label>{{ $bonusGuaranteeForm->bonusesEnabled ? 'Есть бонусы' : 'Нет бонусов' }}</label>
                        <x-permissions.field-guard :enabled="$canEdit" :fill="false">
                            <x-form.toggle-switch wire:model.live="bonusGuaranteeForm.bonusesEnabled" :disabled="! $canEdit" />
                        </x-permissions.field-guard>
                    </div>
                </x-form.form-field>

                @if ($bonusGuaranteeForm->bonusesEnabled)
                    <!-- Расчет в % от суммы чека клиента -->
                    <x-form.form-field>
                        <x-form.form-label>
                            Бонус и/или гарантия рассчитывается в % от суммы чека клиента
                        </x-form.form-label>
                        <div class="flex items-center justify-end gap-3">
                            <label>{{ $bonusGuaranteeForm->calculateInPercentage ? 'Да' : 'Нет' }}</label>
                            <x-permissions.field-guard :enabled="$canEdit" :fill="false">
                                <x-form.toggle-switch wire:model.live="bonusGuaranteeForm.calculateInPercentage" :disabled="! $canEdit" />
                            </x-permissions.field-guard>
                        </div>
                    </x-form.form-field>

                    <!-- С какого месяца начинать считать бонусы и/или гарантию -->
                    <x-form.form-field>
                        <x-form.form-label required>
                            С какого месяца начинать считать бонусы и/или гарантию?
                        </x-form.form-label>
                        <div>
                            <x-permissions.field-guard :enabled="$canEdit">
                                <x-form.select
                                    wire:model="bonusGuaranteeForm.startMonth"
                                    :options="[
                                        ['label' => 'Начиная с 1-го месяца работы', 'value' => 1],
                                        ['label' => 'Начиная со 2-го месяца работы', 'value' => 2],
                                        ['label' => 'Начиная с 3-го месяца работы', 'value' => 3],
                                    ]"
                                    placeholder="Выберите вариант"
                                    :disabled="! $canEdit"
                                />
                            </x-permissions.field-guard>
                        </div>
                    </x-form.form-field>

                    <div class="mt-4 flex flex-col gap-4">
                        <h1>Задайте логику расчета бонуса и/или гарантии</h1>
                        <div class="text-caption-text">
                            Укажите диапазоны выполнения плана и размер бонуса или гарантии
                        </div>

                        <style>
                            .bonus-intervals-wrap {
                                width: fit-content;
                                max-width: 100%;
                                margin-left: auto;
                                margin-right: auto;
                            }

                            .bonus-intervals-grid {
                                grid-template-columns: 40px 120px 40px 120px 32px 140px 56px;
                                column-gap: 16px;
                            }

                            .bonus-intervals-grid .bonus-interval-remove {
                                margin-left: 8px;
                            }

                            .bonus-intervals-grid .bonus-interval-label,
                            .bonus-intervals-grid .bonus-interval-sep {
                                display: flex;
                                min-height: 42px;
                                align-items: center;
                                justify-content: center;
                            }
                        </style>
                        <div class="bonus-intervals-wrap">
                            <div class="bonus-intervals-grid grid items-start gap-y-2 text-[14px]">
                            <div class="text-secondary-text col-span-4">Выполнение плана в % (включительно)</div>
                            <div></div>
                            <div class="text-secondary-text">Бонус и/или гарантия в % от чека клиента</div>
                            <div></div>

                            @foreach ($bonusGuaranteeForm->intervals as $index => $interval)
                                <div class="bonus-interval-label text-secondary-text">От</div>
                                <x-permissions.field-guard :enabled="$canEdit">
                                    <x-form.input-text
                                        type="number"
                                        wire:model.blur="bonusGuaranteeForm.intervals.{{ $index }}.fromPercentage"
                                        wire:blur="validateBonusIntervalField({{ $index }}, 'fromPercentage')"
                                        placeholder="От"
                                        suffix="%"
                                        :disabled="! $canEdit"
                                    />
                                </x-permissions.field-guard>
                                <div class="bonus-interval-label text-secondary-text">До</div>
                                <x-permissions.field-guard :enabled="$canEdit">
                                    <x-form.input-text
                                        type="number"
                                        wire:model.blur="bonusGuaranteeForm.intervals.{{ $index }}.toPercentage"
                                        wire:blur="validateBonusIntervalField({{ $index }}, 'toPercentage')"
                                        placeholder="До"
                                        suffix="%"
                                        :disabled="! $canEdit"
                                    />
                                </x-permissions.field-guard>
                                <div class="bonus-interval-sep text-secondary-text">-</div>
                                @if ($bonusGuaranteeForm->calculateInPercentage)
                                    <x-permissions.field-guard :enabled="$canEdit">
                                        <x-form.input-text
                                            type="number"
                                            wire:model.blur="bonusGuaranteeForm.intervals.{{ $index }}.bonusPercentage"
                                            wire:blur="validateBonusIntervalField({{ $index }}, 'bonusPercentage')"
                                            placeholder="%"
                                            suffix="%"
                                            :allow-negative="true"
                                            :disabled="! $canEdit"
                                        />
                                    </x-permissions.field-guard>
                                @else
                                    <x-permissions.field-guard :enabled="$canEdit">
                                        <x-form.input-text
                                            type="number"
                                            wire:model.blur="bonusGuaranteeForm.intervals.{{ $index }}.bonusAmount"
                                            wire:blur="validateBonusIntervalField({{ $index }}, 'bonusAmount')"
                                            placeholder="₽"
                                            suffix="₽"
                                            :allow-negative="true"
                                            :disabled="! $canEdit"
                                        />
                                    </x-permissions.field-guard>
                                @endif
                                @if ($canEdit)
                                    <div class="bonus-interval-remove pt-1">
                                        <x-button.button
                                            type="button"
                                            wire:click.prevent="removeInterval({{ $index }})"
                                            icon="icons.delete"
                                            title="Удалить"
                                        />
                                    </div>
                                @else
                                    <div class="bonus-interval-remove"></div>
                                @endif
                            @endforeach
                            </div>
                            @if ($canEdit)
                                <div class="mt-3 flex items-center justify-center">
                                    <x-button.button
                                        type="button"
                                        wire:click.prevent="addInterval"
                                        variant="action"
                                    >
                                        <x-slot:label>Добавить диапазон</x-slot:label>
                                    </x-button.button>
                                </div>
                            @endif
                        </div>
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
                        :can-edit="$canEdit"
                    />
                    <x-project-form.integration-settings-card
                        title="Деньги"
                        description="Настройте интеграцию для получения информации по деньгам и актам в канале"
                        :configured-integrations="$this->configuredMoneyIntegrations"
                        modal-trigger-name="money-integrations-modal"
                        :can-edit="$canEdit"
                    />
                    <x-project-form.integration-settings-card
                        title="Аналитика"
                        description="Интеграции, с помощью которых будете получать количество визитов, конверсий или позиции"
                        :configured-integrations="$this->configuredAnalyticsIntegrations"
                        modal-trigger-name="analytics-integrations-modal"
                        :can-edit="$canEdit"
                    />
                </div>
            </div>

            <div
                class="mt-4 flex flex-col gap-4"
                wire:key="parameter-schemes-{{ $clientProjectForm->projectType }}-{{ $clientProjectForm->kpi }}-{{ md5(json_encode($parameterCalculationRows)) }}"
            >
                <div class="flex items-center gap-3">
                    <h1>Настройка параметров</h1>
                    <x-overlay.tooltip>
                        Блок носит информационный характер - для понимания логики расчета параметров в отчетах. Изменить логику расчета параметров вы можете включив или выключив интеграции. После внесения изменений - пересоберите статистику в клиенто-проекте
                    </x-overlay.tooltip>
                </div>

                <x-form.form-field>
                    <x-form.form-label class="font-bold">Фактические параметры</x-form.form-label>
                    <x-form.form-label class="font-bold">Схема расчета параметра</x-form.form-label>
                </x-form.form-field>

                @if (empty($clientProjectForm->projectType) || empty($clientProjectForm->kpi))
                    <span class="text-default-button-disabled flex items-center justify-center text-[18px] italic">
                        Выберите KPI и Тип клиенто-проекта
                    </span>
                @elseif ($parameterCalculationRows === [])
                    <span class="text-default-button-disabled flex items-center justify-center text-[18px] italic">
                        Для выбранных KPI и типа нет схемы параметров
                    </span>
                @else
                    @foreach ($parameterCalculationRows as $row)
                        <x-form.form-field wire:key="parameter-scheme-{{ $row['code'] }}-{{ md5($row['scheme']) }}">
                            <x-form.form-label tooltip="Данные из интеграций поступают с учетом заданных настроек">
                                {{ $row['label'] }}
                            </x-form.form-label>
                            <div class="w-full max-w-[489px]">
                                <textarea
                                    class="border-input-border text-primary-text disabled:bg-secondary min-h-[72px] w-full resize-none rounded-[5px] border px-3 py-2 text-sm leading-5 break-words whitespace-pre-wrap"
                                    rows="3"
                                    disabled
                                    readonly
                                    title="{{ $row['scheme'] }}"
                                >{{ $row['scheme'] }}</textarea>
                            </div>
                        </x-form.form-field>
                    @endforeach
                @endif
            </div>

            <div class="mt-4 flex flex-col gap-4">
                <h1>Пересбор статистики клиенто-проекта</h1>
                <x-form.form-field>
                    <x-form.form-label
                        tooltip="Укажите период за который нужно обновить отчеты с учетом обновленных: целей, счетчиков Метрики, выбранных UTM-меток, условий, интеграций"
                    >Выберите период</x-form.form-label>
                    <div class="flex flex-col gap-2">
                        <div class="flex w-full min-w-0 flex-row items-center gap-2">
                            <div class="min-w-0 flex-1">
                                <x-form.month-picker
                                    wire:model.live="statisticsRebuildFrom"
                                    :max="$this->statisticsRebuildFromMax()"
                                />
                            </div>
                            <span class="shrink-0">-</span>
                            <div class="min-w-0 flex-1">
                                <x-form.month-picker
                                    wire:model.live="statisticsRebuildTo"
                                    :min="$this->statisticsRebuildToMin()"
                                    :max="now()->toDateString()"
                                />
                            </div>
                        </div>
                        <x-permissions.field-guard :enabled="$canEdit">
                            <div
                                @class(['relative block w-full'])
                                @if (! $this->canRebuildStatistics)
                                    x-data="{ open: false }"
                                @endif
                            >
                                <span
                                    class="block w-full"
                                    @if (! $this->canRebuildStatistics)
                                        x-ref="rebuildStatisticsTrigger"
                                        @mouseenter="open = true"
                                        @mouseleave="open = false"
                                    @endif
                                >
                                    <x-button.button
                                        class="w-full"
                                        label="Пересобрать статистику"
                                        :disabled="! $this->canRebuildStatistics"
                                    />
                                </span>
                                @if ($canEdit && ! $this->canRebuildStatistics)
                                    <template x-teleport="body">
                                        <div
                                            class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                                            style="z-index: 1000"
                                            x-show="open"
                                            x-cloak
                                            x-anchor.top="$refs.rebuildStatisticsTrigger"
                                        >
                                            Сначала выберите период
                                        </div>
                                    </template>
                                @endif
                            </div>
                        </x-permissions.field-guard>
                    </div>
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label>Статус обновления</x-form.form-label>
                    <div>
                        <div class="ml-auto rounded-full bg-red-100 px-3 py-1 text-center">
                            Не начато
                        </div>
                    </div>
                </x-form.form-field>
            </div>

            @if ($clientProjectForm->projectType === \Src\Domain\ValueObjects\ProjectType::CONTEXT_AD->value)
                <div class="mt-4 flex flex-col gap-4">
                    <h1>Генерация клиентских отчетов</h1>
                    <div class="text-caption-text">
                        Задайте условия подмены UTM-меток в отчетах
                    </div>

                    {{-- Таблица с логикой расчета — как блок гарантий --}}
                    <style>
                        .utm-mapping-grid {
                            grid-template-columns: minmax(0, 1fr) 32px minmax(0, 1fr) 32px minmax(0, 1fr) 56px;
                        }

                        .utm-mapping-grid .utm-mapping-remove {
                            margin-left: 8px;
                        }

                        .utm-mapping-grid .utm-mapping-sep {
                            display: flex;
                            min-height: 42px;
                            align-items: center;
                            justify-content: center;
                        }
                    </style>
                    <div class="utm-mapping-grid grid w-full items-start gap-x-2 gap-y-2 text-[14px]">
                        <div class="text-secondary-text">Выберите UTM-метку</div>
                        <div></div>
                        <div class="text-secondary-text">Введите значение подменяемой UTM-метки</div>
                        <div></div>
                        <div class="text-secondary-text">Введите значение, которое отобразится в отчете</div>
                        <div></div>

                        <?php /** @var \App\Livewire\Forms\SystemSettings\ClientAndProjects\ProjectUtmMappingForm $utmMappingForm */ ?>
                        @foreach ($utmMappingForm->utmMappings as $index => $utmMappingItem)
                            <x-form.input-text
                                placeholder="Выберите UTM-метку"
                                wire:model.defer="utmMappingForm.utmMappings.{{ $index }}.utmType"
                                disabled
                            />
                            <div class="utm-mapping-sep text-secondary-text">-</div>
                            <x-permissions.field-guard :enabled="$canEdit">
                                <x-form.input-text
                                    placeholder="Введите значение"
                                    wire:model.live="utmMappingForm.utmMappings.{{ $index }}.utmValue"
                                    wire:blur="validateUtmField({{ $index }}, 'utmValue')"
                                    :disabled="! $canEdit"
                                />
                            </x-permissions.field-guard>
                            <div class="utm-mapping-sep text-secondary-text">=</div>
                            <x-permissions.field-guard :enabled="$canEdit">
                                <x-form.input-text
                                    placeholder="Значение в отчете"
                                    wire:model.live="utmMappingForm.utmMappings.{{ $index }}.replacementValue"
                                    wire:blur="validateUtmField({{ $index }}, 'replacementValue')"
                                    :disabled="! $canEdit"
                                />
                            </x-permissions.field-guard>
                            @if ($canEdit)
                                <div class="utm-mapping-remove pt-1">
                                    <x-button.button
                                        type="button"
                                        wire:click.prevent="removeMapping({{ $index }})"
                                        icon="icons.delete"
                                        title="Удалить"
                                    />
                                </div>
                            @else
                                <div class="utm-mapping-remove"></div>
                            @endif
                        @endforeach
                    </div>
                    @if ($canEdit)
                        <div class="flex items-center justify-center">
                            <x-button.button
                                type="button"
                                wire:click.prevent="addMapping"
                                variant="action"
                            >
                                <x-slot:label>Добавить условие</x-slot:label>
                            </x-button.button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
        </x-form.form>
    </x-panel.scroll-panel>

    <template x-if="hasPendingChanges && canEditPage">
        <div class="flex max-w-[950px] justify-between">
            <x-permissions.field-guard :enabled="$canEdit">
                <x-button.button
                    type="button"
                    variant="primary"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    :disabled="! $canEdit || ! $this->canSubmitClientProject"
                >
                    <x-slot:label>
                        Сохранить клиенто-проект
                    </x-slot:label>
                </x-button.button>
            </x-permissions.field-guard>
            <x-button.button
                type="button"
                x-on:click="$wire.cancelChanges()"
            >
                <x-slot:label>
                    Отменить
                </x-slot:label>
            </x-button.button>
        </div>
    </template>

    <x-project-form.integration-list-modal
        name="tools-integrations-modal"
        title="Инструменты"
        :integrations="$this->toolsIntegrations"
        :can-edit="$canEdit"
    />

    <x-project-form.integration-list-modal
        name="money-integrations-modal"
        title="Деньги"
        :integrations="$this->moneyIntegrations"
        :can-edit="$canEdit"
    />

    <x-project-form.integration-list-modal
        name="analytics-integrations-modal"
        title="Аналитика"
        :integrations="$this->analyticsIntegrations"
        :can-edit="$canEdit"
    />

    <x-project-form.integration-settings-modal
        :project-integration="$selectedIntegration"
        :project-id="$clientProjectForm->id"
        :platform-configured="$this->isSelectedIntegrationPlatformConfigured"
        :body-revision="$integrationModalBodyRevision"
        :can-edit="$canEdit"
    />
</div>
