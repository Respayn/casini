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

        /**
         * Подсветка ячейки согласования после смены года без remount.
         */
        syncApprovalHighlight(rowIndex, quarter) {
            const parent = window.Livewire
                ? window.Livewire.all().find((component) => component.name === 'pages::planning')
                : null;
            const approvals = parent
                && parent.$wire
                && parent.$wire.tableData
                && parent.$wire.tableData[rowIndex]
                ? parent.$wire.tableData[rowIndex].approvals
                : null;
            const approval = approvals ? approvals[quarter] : null;

            return !!(approval && approval.approved);
        },
    }"
    x-on:modal-hide.window="
        if ($event.detail.name !== 'planning-leave-guard') return;
        onLeaveGuardHidden();
    "
>
    <x-layout.sidebar-filter-hint />

    {{-- Шапка компонента --}}
    <div class="mb-7 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <h1>Планирование</h1>
            <x-overlay.modal-trigger name="planning-report-settings-modal" wire:click="openReportSettings">
                <button
                    type="button"
                    class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-md text-secondary-text transition hover:text-primary disabled:cursor-not-allowed"
                    title="Настроить отчет"
                    aria-label="Настроить отчет"
                >
                    <x-icons.settings />
                </button>
            </x-overlay.modal-trigger>
        </div>
        <div class="flex items-center">
            <x-button.button icon="icons.save" label="Сохранить изменения" variant="primary"
                loading="save" :disabled="!$hasChanges" wire:click="save" />
        </div>
    </div>

    {{-- Фильтры: разметка как на Каналах/Статистике + год.
         На year-picker не вешаем wire:loading.class — иначе Alpine-цифра года затирается. --}}
    <div class="flex items-center">
        <div class="mr-3.5">
            <label>Неактивные клиенто-проекты:</label>
            <x-form.checkbox wire:model.live="showInactive" />
        </div>

        <div class="mr-[26px]">
            <label>НДС</label>
            <x-form.checkbox wire:model.live="includeVat" />
        </div>

        <div class="relative">
            <div
                wire:loading
                wire:target="year,showInactive,includeVat,applyReportGrouping"
                class="absolute inset-0 z-10"
                style="cursor: wait"
            ></div>
            <x-form.year-picker wire:model.live="year" />
        </div>
    </div>

    {{-- Отступ до таблицы: раньше давал year-picker (margin-bottom 12px), теперь у ряда фильтров --}}
    <x-report.table-loading-bars />
    <div
        class="relative mt-3"
        style="min-height: 240px"
        wire:loading.class="report-table-loading"
        wire:target="year,showInactive,includeVat,applyReportGrouping,onSidebarProjectSelected,onSidebarProjectCleared,clearSidebarProjectFilter"
    >
            @if (empty($tableData))
                <div class="mt-20 flex flex-col items-center gap-4">
                    <span class="text-caption-text">Нет клиенто-проектов для планирования</span>
                    <div>
                        <x-button.button icon="icons.plus" label="Добавить клиенто-проект" variant="primary" />
                    </div>
                </div>
            @else
                @php
                    $isFlatView = $this->isFlatGroupingView;
                    $typeFirst = $this->isTypeFirstColumn();
                @endphp
                <div
                    wire:key="planning-groups-{{ $isFlatView ? 'flat' : $grouping }}"
                    x-data="{ expandedGroups: {}, isFlat: {{ $isFlatView ? 'true' : 'false' }} }"
                >
                <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                    <x-data.table>
                    <x-data.table-columns>
                        @if ($typeFirst)
                            <x-data.table-column class="whitespace-nowrap">
                                Тип клиенто-проекта
                            </x-data.table-column>
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
                        @else
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
                        @endif
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
                        @php
                            $clientProjectStripe = 0;
                        @endphp
                        @foreach ($this->planGroups as $groupIndex => $group)
                            @unless ($isFlatView || $group['label'] === null)
                                @php $groupRowCount = count($group['rows']); @endphp
                                <x-data.table-row wire:key="planning-group.{{ $groupIndex }}.summary">
                                    @if ($typeFirst)
                                        <x-report.group-toggle-cell
                                            :label="$group['label']"
                                            :group-index="$groupIndex"
                                        />
                                        <x-data.table-cell class="bg-table-summary-bg whitespace-nowrap font-bold">
                                            {{ $groupRowCount }}
                                        </x-data.table-cell>
                                        <x-data.table-cell class="bg-table-summary-bg font-bold">
                                            {{ $groupRowCount }}
                                        </x-data.table-cell>
                                        <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                        <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @else
                                        <x-report.group-toggle-cell
                                            :label="$group['label']"
                                            :group-index="$groupIndex"
                                        />
                                        <x-data.table-cell class="bg-table-summary-bg font-bold">
                                            {{ $groupRowCount }}
                                        </x-data.table-cell>
                                        <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                        <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                        <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @endif
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @if ($this->canViewApprovals)
                                        <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @endif
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @if ($this->canViewApprovals)
                                        <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @endif
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @if ($this->canViewApprovals)
                                        <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @endif
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @if ($this->canViewApprovals)
                                        <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                    @endif
                                </x-data.table-row>
                            @endunless
                            @foreach ($group['rows'] as $item)
                                        @php
                                            $rowIndex = $item['index'];
                                            $projectPlan = $item['data'];
                                            $rowBgColor = $clientProjectStripe % 2 === 0 ? '#F9F9F9' : '#FFFFFF';
                                            $clientProjectStripe++;
                                        @endphp
                                        <x-data.table-row
                                            class="h-px"
                                            :bg-color="$rowBgColor"
                                            wire:key="planning-row.{{ $rowIndex }}"
                                            x-bind:class="{ 'report-group-data-row-collapsed': !(isFlat || expandedGroups['group-{{ $groupIndex }}']) }"
                                        >
                                            @if ($typeFirst)
                                                <x-data.table-cell>
                                                    {{ $projectPlan['department'] }}
                                                </x-data.table-cell>
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
                                            @else
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
                                            @endif
                                            <x-data.table-cell>
                                                {{ $projectPlan['kpi'] }}
                                            </x-data.table-cell>
                                            <x-data.table-cell class="!p-0 h-full">
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
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.1" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="2"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.2" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="3"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.3" />

                                            @if ($this->canViewApprovals)
                                                {{-- Подсветка на самой ячейке: в Firefox height 100% у внутреннего блока не работает, синий прямоугольник остаётся по высоте галочки. --}}
                                                <x-data.table-cell
                                                    class="!p-0 text-center"
                                                    style="vertical-align: middle"
                                                    x-data="{ approved: {{ json_encode((bool) ($tableData[$rowIndex]['approvals'][1]['approved'] ?? false)) }}, rowIndex: {{ (int) $rowIndex }}, quarter: 1 }"
                                                    x-on:planning-table-sync.window="approved = syncApprovalHighlight(rowIndex, quarter)"
                                                    x-bind:class="{ 'bg-primary': approved }"
                                                >
                                                    <x-planning.approval-checkbox
                                                        wire:model.live="tableData.{{ $rowIndex }}.approvals.1.approved"
                                                        :can-edit="$this->canEditApprovals"
                                                        :date="$tableData[$rowIndex]['approvals'][1]['date'] ?? null"
                                                        :approved-by-name="$tableData[$rowIndex]['approvals'][1]['approved_by_name'] ?? null"
                                                        :approved="$tableData[$rowIndex]['approvals'][1]['approved'] ?? false"
                                                        :row-index="$rowIndex"
                                                        :quarter="1"
                                                    />
                                                </x-data.table-cell>
                                            @endif

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="4"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.4" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="5"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.5" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="6"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.6" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell
                                                    class="!p-0 text-center"
                                                    style="vertical-align: middle"
                                                    x-data="{ approved: {{ json_encode((bool) ($tableData[$rowIndex]['approvals'][2]['approved'] ?? false)) }}, rowIndex: {{ (int) $rowIndex }}, quarter: 2 }"
                                                    x-on:planning-table-sync.window="approved = syncApprovalHighlight(rowIndex, quarter)"
                                                    x-bind:class="{ 'bg-primary': approved }"
                                                >
                                                    <x-planning.approval-checkbox
                                                        wire:model.live="tableData.{{ $rowIndex }}.approvals.2.approved"
                                                        :can-edit="$this->canEditApprovals"
                                                        :date="$tableData[$rowIndex]['approvals'][2]['date'] ?? null"
                                                        :approved-by-name="$tableData[$rowIndex]['approvals'][2]['approved_by_name'] ?? null"
                                                        :approved="$tableData[$rowIndex]['approvals'][2]['approved'] ?? false"
                                                        :row-index="$rowIndex"
                                                        :quarter="2"
                                                    />
                                                </x-data.table-cell>
                                            @endif

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="7"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.7" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="8"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.8" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="9"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.9" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell
                                                    class="!p-0 text-center"
                                                    style="vertical-align: middle"
                                                    x-data="{ approved: {{ json_encode((bool) ($tableData[$rowIndex]['approvals'][3]['approved'] ?? false)) }}, rowIndex: {{ (int) $rowIndex }}, quarter: 3 }"
                                                    x-on:planning-table-sync.window="approved = syncApprovalHighlight(rowIndex, quarter)"
                                                    x-bind:class="{ 'bg-primary': approved }"
                                                >
                                                    <x-planning.approval-checkbox
                                                        wire:model.live="tableData.{{ $rowIndex }}.approvals.3.approved"
                                                        :can-edit="$this->canEditApprovals"
                                                        :date="$tableData[$rowIndex]['approvals'][3]['date'] ?? null"
                                                        :approved-by-name="$tableData[$rowIndex]['approvals'][3]['approved_by_name'] ?? null"
                                                        :approved="$tableData[$rowIndex]['approvals'][3]['approved'] ?? false"
                                                        :row-index="$rowIndex"
                                                        :quarter="3"
                                                    />
                                                </x-data.table-cell>
                                            @endif

                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="10"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.10" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="11"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.11" />
                                            <livewire:planning.plan-value :parameters="$projectPlan['parameters']" :month="12"
                                                :department="$projectPlan['department']" :kpi="$projectPlan['kpi']" :row-index="$rowIndex"
                                                :can-edit="$this->canEditPlanValues" wire:key="plan.{{ $dataEpoch }}.{{ $rowIndex }}.12" />

                                            @if ($this->canViewApprovals)
                                                <x-data.table-cell
                                                    class="!p-0 text-center"
                                                    style="vertical-align: middle"
                                                    x-data="{ approved: {{ json_encode((bool) ($tableData[$rowIndex]['approvals'][4]['approved'] ?? false)) }}, rowIndex: {{ (int) $rowIndex }}, quarter: 4 }"
                                                    x-on:planning-table-sync.window="approved = syncApprovalHighlight(rowIndex, quarter)"
                                                    x-bind:class="{ 'bg-primary': approved }"
                                                >
                                                    <x-planning.approval-checkbox
                                                        wire:model.live="tableData.{{ $rowIndex }}.approvals.4.approved"
                                                        :can-edit="$this->canEditApprovals"
                                                        :date="$tableData[$rowIndex]['approvals'][4]['date'] ?? null"
                                                        :approved-by-name="$tableData[$rowIndex]['approvals'][4]['approved_by_name'] ?? null"
                                                        :approved="$tableData[$rowIndex]['approvals'][4]['approved'] ?? false"
                                                        :row-index="$rowIndex"
                                                        :quarter="4"
                                                    />
                                                </x-data.table-cell>
                                            @endif
                                        </x-data.table-row>
                            @endforeach
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.scroll-panel>
                </div>
            @endif
    </div>

    <x-overlay.modal name="planning-report-settings-modal" title="Настроить отчет">
        <x-slot:body>
            <div style="min-width: 600px">
                <x-form.form :is-normalized="true" class="[&_.form-field]:!items-center">
                    <x-form.form-field>
                        <x-form.form-label>Сгруппировать отчет</x-form.form-label>
                        <div>
                            <x-form.select
                                wire:model="groupingDraft"
                                :options="[
                                    ['label' => 'Без группировки', 'value' => 'none'],
                                    ['label' => 'По клиентам', 'value' => 'clients'],
                                    ['label' => 'По типу клиенто-проекта', 'value' => 'project_type'],
                                ]"
                            />
                        </div>
                    </x-form.form-field>
                </x-form.form>

                <div class="mt-7 flex justify-between">
                    <x-button
                        icon="icons.check"
                        label="Применить"
                        variant="primary"
                        wire:click="applyReportGrouping"
                        x-on:click="$dispatch('modal-hide', { name: 'planning-report-settings-modal' })"
                    />
                    <x-button
                        label="Отмена"
                        wire:click="cancelReportSettings"
                        x-on:click="$dispatch('modal-hide', { name: 'planning-report-settings-modal' })"
                    />
                </div>
            </div>
        </x-slot>
    </x-overlay.modal>

    <x-overlay.modal name="planning-leave-guard" title="Выйти без сохранения?">
        <x-slot:body>
            <div class="flex flex-col gap-3">
                <x-button.button
                    icon="icons.save"
                    label="Сохранить изменения"
                    variant="primary"
                    x-on:click="saveAndLeave()"
                    loading="saveAndContinue"
                />
                <x-button.button
                    label="Отменить изменения"
                    x-on:click="discardAndLeave()"
                    loading="discardAndContinue"
                />
            </div>
        </x-slot>
    </x-overlay.modal>
</div>