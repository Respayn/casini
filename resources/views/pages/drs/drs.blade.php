@php
    $money = fn (float $value): string => number_format($value, 2, ',', ' ').' ₽';
    $signedMoney = fn (float $value): string => ($value > 0 ? '+' : '').$money($value);
    $loadingTargets = 'month, onlyNew, save, hideOperation, onSidebarProjectSelected, onSidebarProjectCleared, clearSidebarProjectFilter';
    $isCredit = $modalMode === 'credit';
    $calc = $this->calculation;
    $headerClass = 'flex w-full items-center justify-center gap-2';
@endphp

<div>
    <x-layout.sidebar-filter-hint />

    {{-- Шапка --}}
    <div class="flex flex-wrap justify-between gap-3">
        <div class="mb-7 flex items-center gap-2">
            <h1>ДРС</h1>
            <span class="text-caption-text text-sm">(движение рекламных средств)</span>        </div>
        @if ($this->canEdit)
            <div>
                <x-button.button
                    icon="icons.plus"
                    label="Прокредитовать клиента"
                    variant="primary"
                    wire:click="openCredit"
                />
            </div>
        @endif
    </div>

    {{-- Фильтры --}}
    <div
        class="flex flex-wrap items-center gap-x-3 gap-y-3"
        wire:loading.class="pointer-events-none opacity-60"
        wire:target="{{ $loadingTargets }}"
    >
        <x-form.month-picker wire:model.live="month" />

        <div class="flex items-center gap-2">
            <label>{{ $onlyNew ? 'Только новые' : 'Все операции' }}</label>
            <x-form.toggle-switch wire:model.live="onlyNew" />
            <x-overlay.tooltip>
                Новые: операции, которые еще никто не открывал
            </x-overlay.tooltip>
        </div>

        <span class="text-caption-text ml-auto flex items-center gap-1 text-sm">
            Последнее поступление:
            <span class="italic">{{ $this->lastImportLabel ?? 'еще не поступали' }}</span>
            <x-overlay.tooltip>
                Часовой пояс агентства: {{ $this->timezoneLabel }}. Изменить можно в настройках агентства
            </x-overlay.tooltip>
        </span>
    </div>

    @if ($actionMessage)
        <x-feedback.notice
            class="mb-0 mt-3"
            :variant="$actionMessageType === 'error' ? 'error' : 'info'"
        >
            {{ $actionMessage }}
        </x-feedback.notice>
    @endif

    <x-report.table-loading :targets="$loadingTargets">
        @if ($this->operations->isEmpty())
            <div class="mt-20 flex flex-col items-center gap-4">
                <span class="text-caption-text">За выбранный месяц операций нет</span>
            </div>
        @else
            <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                <x-data.table>
                    <x-data.table-columns>
                        <x-data.table-column>
                            <div class="{{ $headerClass }}">
                                <span>№</span>
                                <x-overlay.tooltip>Номер операции в банке или номер выданного кредита</x-overlay.tooltip>
                            </div>
                        </x-data.table-column>
                        <x-data.table-column>
                            <div class="{{ $headerClass }}">
                                <span>Дата</span>
                                <x-overlay.tooltip>Первая дата - это дата поступления платежа или выданного кредита, вторая дата - дата отправки средств в рекламный кабинет</x-overlay.tooltip>
                            </div>
                        </x-data.table-column>
                        <x-data.table-column>
                            <div class="{{ $headerClass }}">
                                <span>Сумма</span>
                                <x-overlay.tooltip>Первая строка - сумма поступления от клиента, вторая - сумма выданного или возвращенного кредита</x-overlay.tooltip>
                            </div>
                        </x-data.table-column>
                        <x-data.table-column><div class="{{ $headerClass }}">Детали</div></x-data.table-column>
                        <x-data.table-column><div class="{{ $headerClass }}">Клиент / клиенто-проект</div></x-data.table-column>
                        <x-data.table-column>
                            <div class="{{ $headerClass }}">
                                <span>Поступит в рекламный кабинет</span>
                                <x-overlay.tooltip>Сбор учтен: пополнение / 1,03</x-overlay.tooltip>
                            </div>
                        </x-data.table-column>
                        @if ($this->canSeeStatus)
                            <x-data.table-column>
                                <div class="{{ $headerClass }}">
                                    <span>Статус</span>
                                    <x-overlay.tooltip>Отметьте, если платеж отправлен в рекламный кабинет</x-overlay.tooltip>
                                </div>
                            </x-data.table-column>
                        @endif
                        <x-data.table-column><div class="{{ $headerClass }}">Сбор 3%</div></x-data.table-column>
                        <x-data.table-column><div class="{{ $headerClass }}">Сбор в копилке</div></x-data.table-column>
                        @if ($this->canSeeInvoice)
                            <x-data.table-column>
                                <div class="{{ $headerClass }}">
                                    <span>Счет выставлен</span>
                                    <x-overlay.tooltip>Счет из рекламного кабинета передан бухгалтеру</x-overlay.tooltip>
                                </div>
                            </x-data.table-column>
                        @endif
                        <x-data.table-column><div class="{{ $headerClass }}">Действия</div></x-data.table-column>
                    </x-data.table-columns>

                    <x-data.table-rows>
                        @foreach ($this->operations as $index => $row)
                            @php
                                $rowBg = match (true) {
                                    $row->creditAmount < 0 => '#FFF5F5',
                                    $row->creditAmount > 0 => '#F0FDF4',
                                    $index % 2 === 0 => '#F9F9F9',
                                    default => '#FFFFFF',
                                };
                            @endphp
                            <x-data.table-row wire:key="drs-row-{{ $row->id }}" :bg-color="$rowBg">
                                <x-data.table-cell class="whitespace-nowrap">
                                    <div class="flex items-center gap-1" title="{{ $row->isManual ? 'Выданный кредит' : 'Поступление на счет' }}">
                                        @if ($row->isManual)
                                            <x-icons.send-money class="text-secondary-text size-5" />
                                        @else
                                            <x-icons.account-balance class="text-secondary-text size-5" />
                                        @endif
                                        <span>{{ $row->number }}</span>
                                        @if ($row->isNew)
                                            <span class="bg-primary rounded px-1.5 text-xs text-white">новая</span>
                                        @endif
                                    </div>
                                </x-data.table-cell>
                                <x-data.table-cell class="whitespace-nowrap">
                                    <div>{{ $row->operationDate }}</div>
                                    @if ($row->sentDate)
                                        <div class="text-caption-text text-xs">В кабинет: {{ $row->sentDate }}</div>
                                    @endif
                                </x-data.table-cell>
                                <x-data.table-cell class="whitespace-nowrap">
                                    @if ($row->bankAmount != 0)
                                        <div class="text-green-700">{{ $money($row->bankAmount) }}</div>
                                    @else
                                        <div class="text-caption-text">-</div>
                                    @endif
                                    @if ($row->creditAmount != 0)
                                        <div @class(['text-[#FF7373]' => $row->creditAmount < 0, 'text-green-700' => $row->creditAmount > 0])>
                                            {{ $signedMoney($row->creditAmount) }}
                                        </div>
                                    @else
                                        <div class="text-caption-text">-</div>
                                    @endif
                                </x-data.table-cell>
                                <x-data.table-cell class="min-w-52 text-sm">
                                    @if ($row->paymentDetails)
                                        <div title="{{ $row->paymentDetails }}">{{ $row->paymentDetails }}</div>
                                    @endif
                                    <div class="text-caption-text mt-1">
                                        @if ($row->advertisingSystem)
                                            <div>Канал: {{ $row->advertisingSystem }}</div>
                                        @endif
                                        @if ($row->invoiceNumber)
                                            <div>Счет: {{ $row->invoiceNumber }}</div>
                                        @endif
                                        @if ($row->managerName)
                                            <div>Менеджер: {{ $row->managerName }}</div>
                                        @endif
                                        @if ($row->comment)
                                            <div>Комментарий: {{ $row->comment }}</div>
                                        @endif
                                    </div>
                                </x-data.table-cell>
                                <x-data.table-cell>
                                    <div class="font-semibold">{{ $row->clientName }}</div>
                                    <div class="text-caption-text text-sm">{{ $row->projectName ?? 'Клиенто-проект не выбран' }}</div>
                                </x-data.table-cell>
                                <x-data.table-cell class="whitespace-nowrap">{{ $money($row->adCabinetAmount) }}</x-data.table-cell>
                                @if ($this->canSeeStatus)
                                    <x-data.table-cell>
                                        <x-overlay.tooltip>
                                            <x-slot:trigger>
                                                <x-form.checkbox
                                                    :checked="$row->isSentToCabinet"
                                                    :disabled="! $this->canEditStatus"
                                                    x-on:change="$wire.toggleStatus({{ $row->id }}, $event.target.checked)"
                                                />
                                            </x-slot:trigger>
                                            {{ $row->statusChangedLabel ?? 'Еще не менялся' }}
                                        </x-overlay.tooltip>
                                    </x-data.table-cell>
                                @endif
                                <x-data.table-cell class="whitespace-nowrap">{{ $money($row->feeAmount) }}</x-data.table-cell>
                                <x-data.table-cell>
                                    <x-form.checkbox
                                        :checked="$row->isFeeInPiggyBank"
                                        :disabled="! $this->canEdit"
                                        x-on:change="$wire.togglePiggyBank({{ $row->id }}, $event.target.checked)"
                                    />
                                </x-data.table-cell>
                                @if ($this->canSeeInvoice)
                                    <x-data.table-cell>
                                        <x-overlay.tooltip>
                                            <x-slot:trigger>
                                                <x-form.checkbox
                                                    :checked="$row->isInvoiceIssued"
                                                    :disabled="! $this->canEditInvoice"
                                                    x-on:change="$wire.toggleInvoice({{ $row->id }}, $event.target.checked)"
                                                />
                                            </x-slot:trigger>
                                            {{ $row->invoiceChangedLabel ?? 'Еще не менялся' }}
                                        </x-overlay.tooltip>
                                    </x-data.table-cell>
                                @endif
                                <x-data.table-cell class="whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <x-button.button
                                            icon="icons.edit"
                                            variant="ghost"
                                            title="Открыть операцию"
                                            wire:click="openOperation({{ $row->id }})"
                                        />
                                        @if ($this->canDelete)
                                            <x-button.button
                                                icon="icons.delete"
                                                variant="ghost"
                                                title="Удалить из ДРС"
                                                wire:click="hideOperation({{ $row->id }})"
                                                wire:confirm="Удалить операцию из ДРС?"
                                            />
                                        @endif
                                    </div>
                                </x-data.table-cell>
                            </x-data.table-row>
                        @endforeach

                        {{-- Итого --}}
                        <x-data.table-row>
                            <x-data.table-cell class="font-bold" colspan="2">Итого</x-data.table-cell>
                            <x-data.table-cell class="whitespace-nowrap font-bold">
                                <div>{{ $money($this->totals['bank']) }}</div>
                                <div>{{ $signedMoney($this->totals['credit']) }}</div>
                            </x-data.table-cell>
                            <x-data.table-cell colspan="2"></x-data.table-cell>
                            <x-data.table-cell class="whitespace-nowrap font-bold">{{ $money($this->totals['cabinet']) }}</x-data.table-cell>
                            @if ($this->canSeeStatus)
                                <x-data.table-cell></x-data.table-cell>
                            @endif
                            <x-data.table-cell class="whitespace-nowrap font-bold">{{ $money($this->totals['fee']) }}</x-data.table-cell>
                            <x-data.table-cell colspan="{{ $this->canSeeInvoice ? 3 : 2 }}"></x-data.table-cell>
                        </x-data.table-row>
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.scroll-panel>
        @endif
    </x-report.table-loading>

    {{-- Окно операции / кредита --}}
    <x-overlay.modal
        name="drs-operation-modal"
        :title="$isCredit ? 'Прокредитовать клиента' : 'Операция № '.($form->number ?? '')"
    >
        <x-slot:body>
            <div class="flex w-[700px] max-w-full flex-col gap-3" wire:key="drs-form-{{ $modalMode }}-{{ $form->id ?? 'new' }}">
                {{-- Галочки --}}
                <div class="flex flex-wrap gap-x-3 gap-y-2">
                    @if ($this->canSeeStatus)
                        <label class="flex items-center gap-2">
                            <x-form.checkbox wire:model.live="form.isSentToCabinet" :disabled="! $this->canEditStatus" />
                            Статус платежа: отправлен в кабинет
                        </label>
                    @endif
                    <label class="flex items-center gap-2">
                        <x-form.checkbox wire:model="form.isFeeInPiggyBank" :disabled="! $this->canEdit" />
                        Сбор в копилке
                    </label>
                    @if ($this->canSeeInvoice)
                        <label class="flex items-center gap-2">
                            <x-form.checkbox wire:model="form.isInvoiceIssued" :disabled="! $this->canEditInvoice" />
                            Счет выставлен
                        </label>
                    @endif
                </div>

                {{-- Даты --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-primary-text text-sm font-semibold">Дата операции</label>
                        @if ($form->isManual && $this->canEdit)
                            <x-form.date-picker wire:model="form.operationDate" placeholder="дд.мм.гггг" />
                        @else
                            <span class="flex min-h-[42px] items-center">{{ $form->operationDate ? \Carbon\Carbon::parse($form->operationDate)->format('d.m.Y') : '' }}</span>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-primary-text text-sm font-semibold">Дата отправки в кабинет</label>
                        @if ($this->canEdit)
                            <x-form.date-picker wire:model="form.sentDate" placeholder="дд.мм.гггг" />
                        @else
                            <span class="flex min-h-[42px] items-center">{{ $form->sentDate ? \Carbon\Carbon::parse($form->sentDate)->format('d.m.Y') : '' }}</span>
                        @endif
                    </div>
                </div>

                {{-- Суммы поступления и кредита --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-primary-text text-sm font-semibold">Сумма поступления</label>
                        @if ($form->isManual)
                            <span class="text-caption-text flex min-h-[42px] items-center">-</span>
                        @else
                            <span class="flex min-h-[42px] items-center text-green-700">{{ $money($form->bankAmount) }}</span>
                        @endif
                    </div>
                    @if ($isCredit)
                        <x-form.input-number
                            label="Сумма кредита *"
                            suffix="₽"
                            wire:model.live.blur="form.creditAmount"
                        />
                    @elseif ($form->isManual || ! $this->canEdit)
                        <div class="flex flex-col gap-2">
                            <label class="text-primary-text text-sm font-semibold">Кредит</label>
                            <span class="flex min-h-[42px] items-center">{{ $signedMoney($form->creditAmount) }}</span>
                        </div>
                    @else
                        <div class="flex flex-col gap-1">
                            <x-form.input-number
                                label="Возврат кредита"
                                suffix="₽"
                                allow-negative
                                wire:model.live.blur="form.creditAmount"
                            />
                            <span class="text-caption-text text-xs">Плюс: клиент вернул кредит из этого поступления</span>
                        </div>
                    @endif
                </div>

                {{-- Клиент и детали --}}
                <div class="grid grid-cols-2 gap-4">
                    @if ($form->isManual && $this->canEdit)
                        <x-form.select
                            label="Клиент *"
                            wire:model.live="form.clientId"
                            :options="$this->clientOptions->all()"
                            label-key="name"
                            value-key="id"
                            placeholder="Выберите клиента"
                        />
                    @else
                        <div class="flex flex-col gap-2">
                            <label class="text-primary-text text-sm font-semibold">Клиент</label>
                            <span class="flex min-h-[42px] items-center">
                                {{ $this->clientOptions->firstWhere('id', $form->clientId)['name'] ?? '' }}
                            </span>
                        </div>
                    @endif
                    <div wire:key="drs-project-{{ $form->clientId ?? 'none' }}">
                        <x-form.select
                            label="Клиенто-проект"
                            wire:model="form.projectId"
                            :options="$this->projectOptions->all()"
                            label-key="name"
                            value-key="id"
                            placeholder="Без клиенто-проекта"
                            empty-placeholder="Сначала выберите клиента"
                            :disabled="! $this->canEdit"
                        />
                    </div>
                    <x-form.select
                        :label="$isCredit ? 'Менеджер *' : 'Менеджер'"
                        wire:model="form.managerId"
                        :options="$this->managerOptions->all()"
                        label-key="name"
                        value-key="id"
                        placeholder="Выберите менеджера"
                        :disabled="! $this->canEdit"
                    />
                    <x-form.select
                        :label="$isCredit ? 'Канал *' : 'Канал'"
                        wire:model="form.advertisingSystem"
                        :options="$this->advertisingSystemOptions"
                        placeholder="Выберите рекламную систему"
                        :disabled="! $this->canEdit"
                    />
                </div>

                @if ($this->creditDebt < 0)
                    <x-feedback.notice variant="error" class="mb-0">
                        Сумма задолженности {{ $this->clientOptions->firstWhere('id', $form->clientId)['name'] ?? 'клиента' }} составляет {{ $money(abs($this->creditDebt)) }}
                    </x-feedback.notice>
                @endif

                {{-- Пополнение и сбор --}}
                <div class="grid grid-cols-2 gap-4">
                    @if ($this->canEdit)
                        <x-form.input-number
                            label="Пополнение кабинета (без учета сбора)"
                            suffix="₽"
                            wire:model.live.blur="form.topUpAmount"
                        />
                    @else
                        <div class="flex flex-col gap-2">
                            <label class="text-primary-text text-sm font-semibold">Пополнение кабинета</label>
                            <span class="flex min-h-[42px] items-center">{{ $money($form->topUpAmount) }}</span>
                        </div>
                    @endif
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-primary-text text-sm font-semibold">Поступит в кабинет</label>
                            <span class="flex min-h-[42px] items-center font-semibold">{{ $money($calc['cabinet']) }}</span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-primary-text text-sm font-semibold">Сбор 3%</label>
                            <span class="flex min-h-[42px] items-center font-semibold">{{ $money($calc['fee']) }}</span>
                        </div>
                    </div>
                </div>

                <label class="flex items-center gap-2">
                    <x-form.checkbox wire:model.live="form.feeIncluded" :disabled="! $this->canEdit" />
                    Удерживать сбор 3% из этого платежа
                </label>

                @if ($this->feeDebt > 0 && $this->canEdit)
                    <x-feedback.notice variant="error" class="mb-0">
                        <div>По клиенту есть задолженность по сбору {{ $money($this->feeDebt) }}. Вычесть ее в этом платеже?</div>
                        <div class="mt-2 flex gap-2">
                            <x-button.button
                                :variant="$form->includeFeeDebt ? 'primary' : null"
                                label="Да, учесть"
                                wire:click="setIncludeFeeDebt(true)"
                            />
                            <x-button.button
                                :variant="$form->includeFeeDebt ? null : 'primary'"
                                label="Не нужно"
                                wire:click="setIncludeFeeDebt(false)"
                            />
                        </div>
                    </x-feedback.notice>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-primary-text text-sm font-semibold">Комментарий</label>
                        <x-form.textarea rows="2" wire:model="form.comment" :disabled="! $this->canEdit" />
                    </div>
                    @if ($form->paymentDetails)
                        <div class="flex flex-col gap-2">
                            <label class="text-primary-text text-sm font-semibold">Назначение платежа</label>
                            <span class="text-caption-text text-sm">{{ $form->paymentDetails }}</span>
                        </div>
                    @endif
                </div>

                @error('form')
                    <span class="text-warning-red text-[12px]">{{ $message }}</span>
                @enderror
            </div>

            <div class="mt-4 flex justify-between">
                @if ($this->canEdit || $this->canEditStatus || $this->canEditInvoice)
                    <x-button.button
                        icon="icons.check"
                        label="Сохранить"
                        variant="primary"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    />
                @endif
                <x-button.button
                    label="Отменить"
                    x-on:click="$dispatch('modal-hide', { name: 'drs-operation-modal' })"
                />
            </div>
        </x-slot>
    </x-overlay.modal>
</div>
