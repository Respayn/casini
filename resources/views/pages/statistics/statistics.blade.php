<div>
    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Статистика</h1>
        <div>
            <x-button.button href="{{ route('system-settings.clients-and-projects') }}" icon="icons.plus"
                label="Добавить клиента" />
            <x-button.button href="{{ route('system-settings.clients-and-projects.projects.manage') }}"
                icon="icons.plus" label="Добавить клиенто-проект" variant="primary" />
        </div>
    </div>

    {{-- Фильтры --}}
    <div class="flex items-center">
        <div class="mr-3.5">
            <label>Неактивные клиенто-проекты:</label>
            <x-form.checkbox wire:model.live="queryData.showInactive" />
        </div>

        <div class="mr-[26px]">
            <label>НДС</label>
            <x-form.checkbox wire:model.live="queryData.includeVat" />
        </div>

        <div>
            <x-form.month-picker wire:model.live="queryData.dateTo" />
        </div>

        <div class="flex-end ml-auto">
            <x-overlay.modal-trigger name="column-settings-modal">
                <x-button.button icon="icons.edit" label="Настроить столбцы" variant="link"
                    wire:click="saveSettingsSnapshot" />
            </x-overlay.modal-trigger>
            <x-overlay.modal-trigger name="group-settings-modal">
                <x-button.button icon="icons.edit" label="Настроить отчет" variant="link" />
            </x-overlay.modal-trigger>
        </div>
    </div>

    @if (!empty($selectedProjects))
        <div class="flex gap-2">
            <div class="w-xs">
                <x-form.select wire:model="bulkAction" :options="[
                ['label' => 'Обновить расходы', 'value' => 'refresh_spendings'],
                ['label' => 'Обновить остаток бюджета', 'value' => 'refresh_budget_remains'],
            ]"
                    placeholder="Массовые действия" />
            </div>
            <x-button.button wire:click="makeBulkAction" label="Выполнить" />
        </div>
    @endif

    @if ($this->reportData->groups->isEmpty())
        <div class="mt-20 flex flex-col items-center gap-4">
            <span class="text-caption-text">Нет клиенто-проектов для отображения каналов</span>
            <div>
                <x-button.button icon="icons.plus" label="Добавить клиенто-проект" variant="primary" />
            </div>
        </div>
    @else
        <div class="mt-3" x-data="{ expandedGroups: {} }">
            <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                <x-data.table>
                    <x-data.table-columns>
                        @foreach ($this->visibleColumns as $column)
                            <x-data.table-column class="whitespace-nowrap">
                                <span>{{ $column->label }}</span>
                                @if ($column->tooltip !== null)
                                    <x-overlay.tooltip>
                                        {{ $column->tooltip }}
                                    </x-overlay.tooltip>
                                @endif
                            </x-data.table-column>
                        @endforeach
                    </x-data.table-columns>
                    <x-data.table-rows>
                        @foreach ($this->reportData->groups as $groupIndex => $group)
                            {{-- Итого по группе --}}
                            @unless (empty($group->summary))
                                <x-data.table-row wire:key="group.{{ $groupIndex }}.name">
                                    <x-data.table-cell colspan="100">
                                        <div class="flex cursor-pointer items-center gap-2"
                                            x-on:click="expandedGroups['group-{{ $groupIndex }}'] = !expandedGroups['group-{{ $groupIndex }}']; console.log(expandedGroups)">
                                            <span class="font-bold">{{ $group->groupLabel }}</span>
                                            <x-icons.accordion-arrow class="transition-transform duration-200"
                                                x-bind:class="{ 'rotate-180': expandedGroups['group-{{ $groupIndex }}'] }" />
                                        </div>
                                    </x-data.table-cell>
                                </x-data.table-row>
                                <x-data.table-row wire:key="group.{{ $groupIndex }}.summary">
                                    @foreach ($this->visibleColumns as $column)
                                        <x-dynamic-component :component="'statistics.rows.summary.' . $column->component"
                                            :params="$group->summary->get($column->field)" />
                                    @endforeach
                                </x-data.table-row>
                            @endunless
                            {{-- Строки группы --}}
                            @foreach ($group->rows as $row)
                                @if ($queryData->grouping->value === 'none')
                                    <x-data.table-row wire:key="row.{{ $row->id }}">
                                        @foreach ($this->visibleColumns as $column)
                                            <x-dynamic-component :component="'statistics.rows.regular.' . $column->component"
                                                :params="$row->data->get($column->field)" />
                                        @endforeach
                                    </x-data.table-row>
                                @else
                                    <x-data.table-row x-show="expandedGroups['group-{{ $groupIndex }}']" wire:key="row.{{ $row->id }}">
                                        @foreach ($this->visibleColumns as $column)
                                            <x-dynamic-component :component="'statistics.rows.regular.' . $column->component"
                                                :params="$row->data->get($column->field)" />
                                        @endforeach
                                    </x-data.table-row>
                                @endif
                            @endforeach
                        @endforeach
                        {{-- Итого по таблице --}}
                        <x-data.table-row>
                            @foreach ($this->visibleColumns as $column)
                                <x-dynamic-component :component="'statistics.rows.summary.' . $column->component"
                                    :params="$this->reportData->summary->get($column->field)" />
                            @endforeach
                        </x-data.table-row>
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.scroll-panel>
        </div>
    @endif

    <x-overlay.modal name="column-settings-modal" title="Настроить столбцы">
        <x-slot:body>
            <div class="flex flex-col gap-2.5" x-data x-sort="$wire.sortColumn($item, $position)">
                @foreach ($queryData->columns as $index => $column)
                    <div class="flex items-center gap-2.5" wire:key="column.{{ $column->field }}"
                        x-sort:item="'{{ $column->field }}'">
                        <x-icons.burger class="text-secondary-text cursor-pointer" x-sort:handle />
                        <x-form.checkbox wire:model="queryData.columns.{{ $index }}.isVisible" />
                        <label>{{ $column->label }}</label>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 flex justify-between">
                <x-button icon="icons.check" label="Применить" variant="primary"
                    x-on:click="$dispatch('modal-hide', { name: 'column-settings-modal' }); $wire.applySettingsSnapshot()" />
                <x-button
                    x-on:click="$dispatch('modal-hide', { name: 'column-settings-modal' }); $wire.dropSettingsSnapshot()"
                    label="Отмена" />
            </div>
            </x-slot>
    </x-overlay.modal>

    <x-overlay.modal name="group-settings-modal" title="Настроить отчет">
        <x-slot:body>
            <div>
                <x-form.form>
                    <x-form.form-field class="w-[603px]">
                        <x-form.form-label>Выделять клиенто-проекты с невыполненными KPI</x-form.form-label>
                        <div>
                            <x-form.select :options="[
        ['label' => 'Да', 'value' => 'Y'],
        ['label' => 'Нет', 'value' => 'N']
    ]"></x-form.select>
                        </div>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label>План и факт накапливаются в отчете</x-form.form-label>
                        <div>
                            <x-form.select :options="[
        ['label' => 'Да', 'value' => 'Y'],
        ['label' => 'Нет', 'value' => 'N']
    ]"></x-form.select>
                        </div>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label>Детализация</x-form.form-label>
                        <div>
                            <x-form.select :options="[
        ['label' => 'По неделям', 'value' => 'by_week'],
        ['label' => 'По месяцам', 'value' => 'by_month']
    ]"></x-form.select>
                        </div>
                    </x-form.form-field>

                    <x-form.form-field>
                        <x-form.form-label>Сгруппировать отчет</x-form.form-label>
                        <div>
                            <x-form.select :options="[
        ['label' => 'Без группировки', 'value' => 'none'],
        ['label' => 'По ролям пользователей', 'value' => 'roles'],
        ['label' => 'По клиентам', 'value' => 'clients'],
        ['label' => 'По отделам', 'value' => 'departments'],
        ['label' => 'По инструментам', 'value' => 'tools'],
    ]" wire:model="queryData.grouping"></x-form.select>
                        </div>
                    </x-form.form-field>
                </x-form.form>

                <div class="mt-7 flex justify-between">
                    <x-button icon="icons.check" label="Применить" variant="primary" />
                    <x-button label="Отмена" />
                </div>
            </div>
            </x-slot>
    </x-overlay.modal>
</div>