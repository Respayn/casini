<div>
    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Планирование</h1>
        <div>
            <x-button.button icon="icons.save" label="Сохранить изменения" variant="primary"
                wire:loading.attr="disabled" :disabled="!$hasChanges" wire:click="save" />
        </div>
    </div>

    <div class="w-48">
        <x-form.year-picker wire:model.live="year" />
    </div>

    @if (empty($tableData))
        <div class="mt-20 flex flex-col items-center gap-4">
            <span class="text-caption-text">Нет клиенто-проектов для планирования</span>
            <div>
                <x-button.button icon="icons.plus" label="Добавить клиенто-проект" variant="primary" />
            </div>
        </div>
    @else
        <div class="mt-3">
            <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                <x-data.table>
                    <x-data.table-columns>
                        <x-data.table-column class="whitespace-nowrap">
                            Клиент
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap">
                            Клиенто-проект
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap">
                            Клиенто-проект создан
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap">
                            ID
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap">
                            Отдел
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap">
                            KPI
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap">
                            Параметр
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Январь
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Февраль
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Март
                        </x-data.table-column>

                        @if ($this->canViewApprovals)
                            <x-data.table-column class="whitespace-nowrap">
                                Согласование
                            </x-data.table-column>
                        @endif

                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Апрель
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Май
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Июнь
                        </x-data.table-column>

                        @if ($this->canViewApprovals)
                            <x-data.table-column class="whitespace-nowrap">
                                Согласование
                            </x-data.table-column>
                        @endif

                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Июль
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Август
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Сентябрь
                        </x-data.table-column>

                        @if ($this->canViewApprovals)
                            <x-data.table-column class="whitespace-nowrap">
                                Согласование
                            </x-data.table-column>
                        @endif

                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Октябрь
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Ноябрь
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[116px]">
                            Декабрь
                        </x-data.table-column>

                        @if ($this->canViewApprovals)
                            <x-data.table-column class="whitespace-nowrap">
                                Согласование
                            </x-data.table-column>
                        @endif
                    </x-data.table-columns>

                    <x-data.table-rows>
                        @foreach ($tableData as $projectPlan)
                                        @php $rowIndex = $loop->index; @endphp
                                        <x-data.table-row>
                                            <x-data.table-cell>
                                                <a class="text-primary underline" href="{{ route('system-settings.clients-and-projects') }}"
                                                    wire:navigate>
                                                    {{ $projectPlan['client_name'] }}
                                                </a>
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                <a class="text-primary underline" href="{{ route('system-settings.clients-and-projects.projects.manage', [
                                'projectId' => $projectPlan['project_id'],
                            ]) }}" wire:navigate>
                                                    {{ $projectPlan['project_name'] }}
                                                </a>
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                {{ $projectPlan['project_created_at'] }}
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                №{{ $projectPlan['project_id'] }}
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                {{ $projectPlan['department'] }}
                                            </x-data.table-cell>
                                            <x-data.table-cell>
                                                {{ $projectPlan['kpi'] }}
                                            </x-data.table-cell>
                                            <x-data.table-cell class="!p-0 h-1">
                                                <div class="grid auto-rows-fr h-full divide-y divide-table-cell">
                                                    @foreach ($projectPlan['parameters'] as $param)
                                                        <div
                                                            class="flex grow items-center whitespace-nowrap justify-between ps-2.5 pe-0.5 py-2 gap-5">
                                                            <span>{{ $param['name'] }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </x-data.table-cell>

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="1"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.1" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="2"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.2" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="3"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.3" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell @class(['bg-primary' => $tableData[$rowIndex]['approvals'][1]])>
                                                    <div class="text-center">
                                                        <x-form.checkbox wire:model.live="tableData.{{ $rowIndex }}.approvals.1"
                                                            :disabled="!$this->canEditApprovals" />
                                                    </div>
                                                </x-data.table-cell>
                                            @endif

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="4"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.4" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="5"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.5" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="6"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.6" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell @class(['bg-primary' => $tableData[$rowIndex]['approvals'][2]])>
                                                    <div class="text-center">
                                                        <x-form.checkbox wire:model.live="tableData.{{ $rowIndex }}.approvals.2"
                                                            :disabled="!$this->canEditApprovals" />
                                                    </div>
                                                </x-data.table-cell>
                                            @endif

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="7"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.7" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="8"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.8" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="9"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.9" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell @class(['bg-primary' => $tableData[$rowIndex]['approvals'][3]])>
                                                    <div class="text-center">
                                                        <x-form.checkbox wire:model.live="tableData.{{ $rowIndex }}.approvals.3"
                                                            :disabled="!$this->canEditApprovals" />
                                                    </div>
                                                </x-data.table-cell>
                                            @endif

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="10"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.10" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="11"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.11" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="12"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $rowIndex }}.12" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell @class(['bg-primary' => $tableData[$rowIndex]['approvals'][4]])>
                                                    <div class="text-center">
                                                        <x-form.checkbox wire:model.live="tableData.{{ $rowIndex }}.approvals.4"
                                                            :disabled="!$this->canEditApprovals" />
                                                    </div>
                                                </x-data.table-cell>
                                            @endif
                                        </x-data.table-row>
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.scroll-panel>
        </div>
    @endif
</div>