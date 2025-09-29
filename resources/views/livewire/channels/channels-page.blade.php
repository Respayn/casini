<div>
    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Каналы:</h1>
        <div>
            <x-button.button
                href="{{ route('system-settings.clients-and-projects') }}"
                icon="icons.plus"
                label="Добавить клиента"
            />
            <x-button.button
                href="{{ route('system-settings.clients-and-projects.projects.manage') }}"
                icon="icons.plus"
                label="Добавить клиенто-проект"
                variant="primary"
            />
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

        <div class="flex gap-2">
            <x-form.month-picker wire:model.live="queryData.dateFrom" />
            <x-form.month-picker wire:model.live="queryData.dateTo" />
        </div>

        <div class="flex-end ml-auto">
            <x-overlay.modal-trigger name="column-settings-modal">
                <x-button.button
                    icon="icons.edit"
                    label="Настроить столбцы"
                    variant="link"
                />
            </x-overlay.modal-trigger>
            <x-overlay.modal-trigger name="group-settings-modal">
                <x-button.button
                    icon="icons.edit"
                    label="Настроить отчет"
                    variant="link"
                />
            </x-overlay.modal-trigger>
        </div>
    </div>

    @if ($hasNoProjects)
        <div class="mt-20 flex flex-col items-center gap-4">
            <span class="text-caption-text">Нет клиенто-проектов для отображения каналов</span>
            <div>
                <x-button.button
                    icon="icons.plus"
                    label="Добавить клиенто-проект"
                    variant="primary"
                />
            </div>
        </div>
    @else
        <div
            class="mt-3"
            x-data="{ expandedGroups: {} }"
        >
            <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                <x-data.table>
                    <x-data.table-columns>
                        @foreach ($this->visibleColumns as $column)
                            <x-data.table-column class="whitespace-nowrap">
                                {{ $column->label }}
                            </x-data.table-column>
                        @endforeach
                    </x-data.table-columns>
                    <x-data.table-rows>
                        @foreach ($reportData->groups as $groupIndex => $group)
                            {{-- Итого по группе --}}
                            @unless (empty($group->summary))
                                <x-data.table-row>
                                    <x-data.table-cell colspan="100">
                                        <div
                                            class="flex cursor-pointer items-center gap-2"
                                            x-on:click="expandedGroups['group-{{ $groupIndex }}'] = !expandedGroups['group-{{ $groupIndex }}']; console.log(expandedGroups)"
                                        >
                                            <span class="font-bold">{{ $group->groupLabel }}</span>
                                            <x-icons.accordion-arrow
                                                class="transition-transform duration-200"
                                                x-bind:class="{ 'rotate-180': expandedGroups['group-{{ $groupIndex }}'] }"
                                            />
                                        </div>
                                    </x-data.table-cell>
                                </x-data.table-row>
                                <x-data.table-row>
                                    @foreach ($this->visibleColumns as $column)
                                        <x-dynamic-component
                                            :component="'channels.rows.summary.' . $column->component"
                                            :params="$group->summary->get($column->field)"
                                        />
                                    @endforeach
                                </x-data.table-row>
                            @endunless
                            {{-- Строки группы --}}
                            @foreach ($group->rows as $row)
                                @if ($queryData->grouping->value !== 'none')
                                    <x-data.table-row x-show="expandedGroups['group-{{ $groupIndex }}']">
                                        @foreach ($this->visibleColumns as $column)
                                            <x-dynamic-component
                                                :component="'channels.rows.regular.' . $column->component"
                                                :params="$row->get($column->field)"
                                            />
                                        @endforeach
                                    </x-data.table-row>
                                @else
                                    <x-data.table-row>
                                        @foreach ($this->visibleColumns as $column)
                                            <x-dynamic-component
                                                :component="'channels.rows.regular.' . $column->component"
                                                :params="$row->get($column->field)"
                                            />
                                        @endforeach
                                    </x-data.table-row>
                                @endif
                            @endforeach
                        @endforeach
                        {{-- Итого по таблице --}}
                        <x-data.table-row>
                            @foreach ($this->visibleColumns as $column)
                                <x-dynamic-component
                                    :component="'channels.rows.summary.' . $column->component"
                                    :params="$reportData->summary->get($column->field)"
                                />
                            @endforeach
                        </x-data.table-row>
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.scroll-panel>
        </div>
    @endif

    <x-overlay.modal
        name="column-settings-modal"
        title="Настроить столбцы"
    >
        <x-slot:body>
            <div
                class="flex flex-col gap-2.5"
                x-data
                x-sort="$wire.sortColumn($item, $position)"
            >
                @foreach ($queryData->columns as $index => $column)
                    <div
                        class="flex items-center gap-2.5"
                        wire:key="{{ $column->field }}"
                        x-sort:item="'{{ $column->field }}'"
                    >
                        <x-icons.burger
                            class="text-secondary-text cursor-pointer"
                            x-sort:handle
                        />
                        <x-form.checkbox wire:model="queryData.columns.{{ $index }}.isVisible" />
                        <label>{{ $column->label }}</label>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 flex justify-between">
                <x-button
                    icon="icons.check"
                    label="Применить"
                    variant="primary"
                    x-on:click="$dispatch('modal-hide', { name: 'column-settings-modal' }); $wire.$refresh()"
                />
                <x-button
                    x-on:click="$dispatch('modal-hide', { name: 'column-settings-modal' })"
                    label="Отмена"
                />
            </div>
        </x-slot>
    </x-overlay.modal>

    <x-overlay.modal
        name="group-settings-modal"
        title="Настроить отчет"
    >
        <x-slot:body>
            <div>
                <div class="flex max-w-[658px] flex-wrap gap-2.5">
                    <x-button.button
                        :variant="$queryData->grouping->value === 'none' ? 'primary' : null"
                        wire:click="applyGrouping('none')"
                        label="Без группировки"
                    />
                    {{-- TODO: Динамически по ролям --}}
                    <x-button.button label='По роли "SEO-специалист"' />
                    <x-button.button label='По роли "PPC-специалист"' />
                    <x-button.button label='По роли "Менеджер"' />
                    {{-- END TODO  --}}
                    <x-button.button label="По клиентам" />
                    <x-button.button
                        :variant="$queryData->grouping->value === 'project_type' ? 'primary' : null"
                        wire:click="applyGrouping('project_type')"
                        label="По отделам"
                    />
                    <x-button.button label="По инструментам" />
                </div>

                <div class="mt-24 flex justify-between">
                    <x-button
                        icon="icons.check"
                        label="Применить"
                        variant="primary"
                        x-on:click="$dispatch('modal-hide', { name: 'group-settings-modal' }); $wire.$refresh()"
                    />
                    <x-button
                        x-on:click="$dispatch('modal-hide', { name: 'group-settings-modal' })"
                        label="Отмена"
                    />
                </div>
            </div>
        </x-slot>
    </x-overlay.modal>
</div>
