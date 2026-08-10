<div
    x-data="{
        pendingUrl: null,
        navigateHandler: null,
        beforeUnloadHandler: null,

        init() {
            this.navigateHandler = (event) => {
                if (! $wire.hasChanges) {
                    return;
                }

                // Уход на другую страницу / продукт — спрашиваем про сохранение.
                event.preventDefault();
                this.pendingUrl = event.detail.url.href;
                this.$dispatch('modal-show', { name: 'planning-leave-guard' });
            };

            this.beforeUnloadHandler = (event) => {
                if (! $wire.hasChanges) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            };

            document.addEventListener('livewire:navigate', this.navigateHandler);
            window.addEventListener('beforeunload', this.beforeUnloadHandler);
        },

        destroy() {
            if (this.navigateHandler) {
                document.removeEventListener('livewire:navigate', this.navigateHandler);
            }
            if (this.beforeUnloadHandler) {
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
            }
        },

        async saveAndLeave() {
            const url = this.pendingUrl;
            this.pendingUrl = null;
            await $wire.saveAndContinue(url);
        },

        async discardAndLeave() {
            const url = this.pendingUrl;
            this.pendingUrl = null;
            await $wire.discardAndContinue(url);
        },

        onLeaveGuardHidden() {
            this.pendingUrl = null;
            $wire.cancelLeaveGuard();
        },
    }"
    x-on:modal-hide.window="
        if ($event.detail.name !== 'planning-leave-guard') return;
        onLeaveGuardHidden();
    "
>
    <x-layout.sidebar-filter-hint />

    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Планирование</h1>
        <div>
            <x-button.button icon="icons.save" label="Сохранить изменения" variant="primary"
                wire:loading.attr="disabled" :disabled="!$hasChanges" wire:click="save" />
        </div>
    </div>

    {{-- Фильтры --}}
    <div
        class="w-48"
        wire:loading.class="pointer-events-none opacity-60"
        wire:target="year"
    >
        <x-form.year-picker wire:model.live="year" />
    </div>

    {{-- Контент остаётся на месте; скелетон — оверлей, без прыжка высоты --}}
    <div class="relative mt-3" style="min-height: 240px">
        <div
            wire:loading
            wire:target="year"
            class="absolute inset-0 z-10 overflow-hidden"
            style="background-color: rgba(255, 255, 255, 0.75)"
        >
            <x-planning.table-skeleton class="h-full" style="min-height: 100%" />
        </div>

        <div
            wire:loading.class="pointer-events-none opacity-40"
            wire:target="year"
        >
            @if (empty($tableData))
                <div class="mt-20 flex flex-col items-center gap-4">
                    <span class="text-caption-text">Нет клиенто-проектов для планирования</span>
                    <div>
                        <x-button.button icon="icons.plus" label="Добавить клиенто-проект" variant="primary" />
                    </div>
                </div>
            @else
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
                            Тип клиенто-проекта
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap">
                            KPI
                        </x-data.table-column>
                        <x-data.table-column class="whitespace-nowrap min-w-[180px]">
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
                                        @php
                                            $rowIndex = $loop->index;
                                            $rowBgColor = $rowIndex % 2 === 0 ? '#F9F9F9' : '#FFFFFF';
                                        @endphp
                                        <x-data.table-row :bg-color="$rowBgColor">
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
                                                            @class([
                                                                'flex grow items-center whitespace-nowrap justify-between px-2.5 py-2 gap-5',
                                                                'cursor-not-allowed' => ! empty($param['is_calculated']),
                                                            ])
                                                            @if (! empty($param['is_calculated']))
                                                                x-data="{ open: false }"
                                                            @endif
                                                        >
                                                            <span
                                                                @class(['font-bold' => ! empty($param['highlight']), 'w-full' => ! empty($param['is_calculated'])])
                                                                @if (! empty($param['is_calculated']))
                                                                    x-ref="autoCalcTrigger"
                                                                    @mouseenter="open = true"
                                                                    @mouseleave="open = false"
                                                                @endif
                                                            >{{ $param['name'] }}</span>
                                                            @if (! empty($param['is_calculated']))
                                                                <template x-teleport="body">
                                                                    <div
                                                                        class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                                                                        style="z-index: 1000"
                                                                        x-show="open"
                                                                        x-cloak
                                                                        x-anchor.top="$refs.autoCalcTrigger"
                                                                    >
                                                                        Рассчитывается автоматически
                                                                    </div>
                                                                </template>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </x-data.table-cell>

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="1"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.1" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="2"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.2" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="3"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.3" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell @class(['bg-primary' => $tableData[$rowIndex]['approvals'][1]])>
                                                    <div class="text-center">
                                                        <x-planning.approval-checkbox
                                                            wire:model.live="tableData.{{ $rowIndex }}.approvals.1"
                                                            :can-edit="$this->canEditApprovals"
                                                        />
                                                    </div>
                                                </x-data.table-cell>
                                            @endif

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="4"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.4" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="5"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.5" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="6"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.6" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell @class(['bg-primary' => $tableData[$rowIndex]['approvals'][2]])>
                                                    <div class="text-center">
                                                        <x-planning.approval-checkbox
                                                            wire:model.live="tableData.{{ $rowIndex }}.approvals.2"
                                                            :can-edit="$this->canEditApprovals"
                                                        />
                                                    </div>
                                                </x-data.table-cell>
                                            @endif

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="7"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.7" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="8"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.8" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="9"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.9" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell @class(['bg-primary' => $tableData[$rowIndex]['approvals'][3]])>
                                                    <div class="text-center">
                                                        <x-planning.approval-checkbox
                                                            wire:model.live="tableData.{{ $rowIndex }}.approvals.3"
                                                            :can-edit="$this->canEditApprovals"
                                                        />
                                                    </div>
                                                </x-data.table-cell>
                                            @endif

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="10"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.10" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="11"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.11" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="12"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $year }}.{{ $dataEpoch }}.{{ $rowIndex }}.12" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell @class(['bg-primary' => $tableData[$rowIndex]['approvals'][4]])>
                                                    <div class="text-center">
                                                        <x-planning.approval-checkbox
                                                            wire:model.live="tableData.{{ $rowIndex }}.approvals.4"
                                                            :can-edit="$this->canEditApprovals"
                                                        />
                                                    </div>
                                                </x-data.table-cell>
                                            @endif
                                        </x-data.table-row>
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.scroll-panel>
            @endif
        </div>
    </div>

    <x-overlay.modal name="planning-leave-guard" title="Выйти без сохранения?">
        <x-slot:body>
            <div class="flex flex-col gap-3">
                <x-button.button
                    icon="icons.save"
                    label="Сохранить изменения"
                    variant="primary"
                    x-on:click="saveAndLeave()"
                    wire:loading.attr="disabled"
                    wire:target="saveAndContinue"
                />
                <x-button.button
                    label="Отменить изменения"
                    x-on:click="discardAndLeave()"
                    wire:loading.attr="disabled"
                    wire:target="discardAndContinue"
                />
            </div>
        </x-slot>
    </x-overlay.modal>
</div>