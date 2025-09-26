<div>
    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Каналы:</h1>
        <div>
            <x-button.button
                icon="icons.plus"
                label="Добавить клиента"
                href="{{ route('system-settings.clients-and-projects') }}"
            />
            <x-button.button
                icon="icons.plus"
                label="Добавить клиенто-проект"
                variant="primary"
                href="{{ route('system-settings.clients-and-projects.projects.manage') }}"
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
            <x-form.month-picker wire:model="queryData.dateFrom" />
            <x-form.month-picker wire:model="queryData.dateTo" />
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
        <div class="mt-3">
            <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                <x-data.table>
                    <x-data.table-columns>
                        @foreach ($this->visibleColumns as $column)
                            <x-data.table-column>
                                {{ $column->label }}
                            </x-data.table-column>
                        @endforeach
                    </x-data.table-columns>
                    <x-data.table-rows>
                        @foreach ($reportData->groups as $group)
                            {{-- Строки группы --}}
                            @foreach ($group->rows as $row)
                                <x-data.table-row>
                                    @foreach ($this->visibleColumns as $column)
                                        <x-data.table-cell>
                                            {{ $row->get($column->field) }}
                                        </x-data.table-cell>
                                    @endforeach
                                </x-data.table-row>
                            @endforeach
                            {{-- Итого по группе --}}
                            <x-data.table-row>
                                @foreach ($this->visibleColumns as $column)
                                    <x-data.table-cell class="bg-table-summary-bg">
                                        {{ $group->summary->get($column->field) }}
                                    </x-data.table-cell>
                                @endforeach
                            </x-data.table-row>
                        @endforeach
                        {{-- Итого по таблице --}}
                        <x-data.table-row>
                            @foreach ($this->visibleColumns as $column)
                                <x-data.table-cell class="bg-table-summary-bg">
                                    {{ $reportData->summary->get($column->field) }}
                                </x-data.table-cell>
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
            <div class="flex flex-wrap gap-2.5 max-w-[658px]">
                <x-button.button label="Без группировки" />
                {{-- TODO: Динамически по ролям --}}
                <x-button.button label='По роли "SEO-специалист"' />
                <x-button.button label='По роли "PPC-специалист"' />
                <x-button.button label='По роли "Менеджер"' />
                {{-- END TODO  --}}
                <x-button.button label="По клиентам" />
                <x-button.button label="По отделам" />
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
        </x-slot>
    </x-overlay.modal>
</div>
