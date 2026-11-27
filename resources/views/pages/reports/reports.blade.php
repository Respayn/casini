<div
    x-data
    x-init="
        @if($pendingDownloadId)
            $nextTick(() => $wire.download({{ $pendingDownloadId }}))
        @endif
    "
>
    <x-layout.sidebar-filter-hint />

    @foreach ($generationWarnings as $warning)
        <x-feedback.notice variant="error">{{ $warning }}</x-feedback.notice>
    @endforeach

    {{-- Шапка компонента --}}
    <div class="mb-7 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <h1>Отчеты</h1>
            <x-overlay.modal-trigger name="reports-report-settings-modal" wire:click="openReportSettings">
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
        <div class="flex items-center gap-2.5">
            @if (auth()->user()?->can('read report templates') || auth()->user()?->can('edit report templates') || auth()->user()?->can('full report templates'))
                <x-button.button href="{{ route('reports.templates') }}" label="Шаблоны отчетов" />
            @endif
            @if ($this->canCreateReports)
                <x-button.button href="{{ route('reports.create') }}" icon="icons.plus" label="Создать отчет"
                    variant="primary" />
            @endif
        </div>
    </div>

    {{-- Фильтры --}}
    @php
        $reportLoadingTargets = 'showInactiveProjects, periodFrom, periodTo, applyColumnSettings, applyReportGrouping, onSidebarProjectSelected, onSidebarProjectCleared, clearSidebarProjectFilter';
    @endphp
    <div
        class="flex flex-wrap items-center gap-y-3"
        wire:loading.class="pointer-events-none opacity-60"
        wire:target="{{ $reportLoadingTargets }}"
    >
        <div class="mr-3.5 flex items-center gap-2">
            <label>Неактивные клиенто-проекты:</label>
            <x-form.checkbox wire:model.live="showInactiveProjects" />
        </div>

        <div class="mr-[26px] flex items-center gap-2">
            <x-form.month-picker wire:model.live="periodFrom" disable-future />
            <span class="text-secondary-text">-</span>
            <x-form.month-picker wire:model.live="periodTo" disable-future />
        </div>

        <x-report.table-toolbar :refreshable="false" :column-trigger-action="false" />
    </div>

    <x-report.table-loading :targets="$reportLoadingTargets">
    @if (empty($this->reports))
        <div class="mt-20 flex flex-col items-center gap-4">
            <span class="text-caption-text">Нет отчетов для отображения</span>
            @if ($this->canCreateReports)
                <div>
                    <x-button.button href="{{ route('reports.create') }}" icon="icons.plus" label="Создать отчет"
                        variant="primary" />
                </div>
            @endif
        </div>
    @else
        @php $isFlatView = $this->isFlatGroupingView; @endphp
        <div
            wire:key="reports-groups-{{ $isFlatView ? 'flat' : $grouping }}"
            x-data="{ expandedGroups: {}, isFlat: {{ $isFlatView ? 'true' : 'false' }} }"
        >
            <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                <x-data.table class="w-full">
                    <x-data.table-columns>
                        @foreach ($this->visibleColumns as $column)
                            <x-data.table-column>{{ $column['label'] }}</x-data.table-column>
                        @endforeach
                    </x-data.table-columns>
                    <x-data.table-rows>
                        @php $pinnedField = $this->pinnedColumnKey(); @endphp
                        @foreach ($this->reportGroups as $groupIndex => $group)
                            @unless ($isFlatView || $group['label'] === null)
                                @php $groupRowCount = count($group['rows']); @endphp
                                <x-data.table-row wire:key="reports-group.{{ $groupIndex }}.summary">
                                    @foreach ($this->visibleColumns as $column)
                                        @if ($pinnedField !== null && $column['key'] === $pinnedField)
                                            <x-report.group-toggle-cell
                                                :label="$group['label']"
                                                :group-index="$groupIndex"
                                            />
                                        @elseif (in_array($column['key'], ['client', 'project'], true))
                                            <x-data.table-cell @class([
                                                'bg-table-summary-bg',
                                                'font-bold',
                                                'whitespace-nowrap' => $column['key'] === 'client',
                                            ])>
                                                {{ $groupRowCount }}
                                            </x-data.table-cell>
                                        @else
                                            <x-data.table-cell class="bg-table-summary-bg">-</x-data.table-cell>
                                        @endif
                                    @endforeach
                                </x-data.table-row>
                            @endunless
                            @foreach ($group['rows'] as $report)
                            <x-data.table-row
                                wire:key="report-row.{{ $report->reportId }}"
                                x-bind:class="{ 'report-group-data-row-collapsed': !(isFlat || expandedGroups['group-{{ $groupIndex }}']) }"
                            >
                                @foreach ($this->visibleColumns as $column)
                                    @switch($column['key'])
                                        @case('date')
                                            <x-data.table-cell class="whitespace-nowrap">
                                                {{ $report->createdAt->format('d.m.y, H:i') }}
                                            </x-data.table-cell>
                                        @break

                                        @case('template')
                                            <x-data.table-cell>{{ $report->templateName }}</x-data.table-cell>
                                        @break

                                        @case('client')
                                            <x-data.table-cell>
                                                <a class="text-primary underline" href="{{ route('system-settings.clients-and-projects') }}"
                                                    wire:navigate>
                                                    {{ $report->clientName }}
                                                </a>
                                            </x-data.table-cell>
                                        @break

                                        @case('id')
                                            <x-data.table-cell>№{{ $report->reportId }}</x-data.table-cell>
                                        @break

                                        @case('channel')
                                            <x-data.table-cell>{{ $report->channelGroupLabel }}</x-data.table-cell>
                                        @break

                                        @case('project')
                                            <x-data.table-cell>
                                                <a class="text-primary underline" href="{{ route('system-settings.clients-and-projects.projects.manage', [
                                                    'projectId' => $report->projectId,
                                                ]) }}" wire:navigate>
                                                    {{ $report->projectName }}
                                                </a>
                                            </x-data.table-cell>
                                        @break

                                        @case('period')
                                            <x-data.table-cell class="whitespace-nowrap">
                                                {{ $report->periodLabel() }}
                                            </x-data.table-cell>
                                        @break

                                        @case('specialist')
                                            <x-data.table-cell class="whitespace-nowrap">{{ $report->specialistName }}</x-data.table-cell>
                                        @break

                                        @case('format')
                                            <x-data.table-cell>{{ $report->format }}</x-data.table-cell>
                                        @break

                                        @case('download')
                                            <x-data.table-cell class="whitespace-nowrap">
                                                <div class="flex gap-2">
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-default-button text-default-button transition hover:not-disabled:bg-default-button hover:not-disabled:text-white disabled:cursor-not-allowed disabled:border-0 disabled:bg-secondary disabled:text-default-button-disabled"
                                                        wire:click="download({{ $report->reportId }})"
                                                        wire:loading.class="pointer-events-none"
                                                        wire:target="download({{ $report->reportId }})"
                                                        title="Скачать"
                                                    >
                                                        <span class="relative inline-flex size-6 shrink-0 items-center justify-center">
                                                            <x-icons.download
                                                                class="size-6"
                                                                wire:loading.remove
                                                                wire:target="download({{ $report->reportId }})"
                                                            />
                                                            <x-icons.progress-activity
                                                                class="absolute inset-0 size-6 animate-spin"
                                                                wire:loading
                                                                wire:target="download({{ $report->reportId }})"
                                                            />
                                                        </span>
                                                    </button>
                                                    @if ($this->canCreateReports)
                                                        <x-button.button
                                                            href="{{ route('reports.create', $report->reportId) }}"
                                                            icon="icons.edit"
                                                            title="Редактировать"
                                                        />
                                                    @endif
                                                    @if ($this->canDeleteReports)
                                                        <x-button.button icon="icons.delete" title="Удалить"
                                                            x-on:click="$dispatch('report-delete-ask', { id: {{ $report->reportId }} })" />
                                                    @endif
                                                </div>
                                            </x-data.table-cell>
                                        @break
                                    @endswitch
                                @endforeach
                            </x-data.table-row>
                            @endforeach
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.scroll-panel>
        </div>
    @endif
    </x-report.table-loading>

    <x-column-settings-modal
        :columns="$this->columnSettingsForModal()"
        :pinned-key="$this->pinnedColumnKey()"
    />

    <x-overlay.modal name="reports-report-settings-modal" title="Настроить отчет">
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
                        label="Применить изменения для всех"
                        variant="primary"
                        wire:click="applyReportGrouping"
                        x-on:click="$dispatch('modal-hide', { name: 'reports-report-settings-modal' })"
                    />
                    <x-button
                        label="Отмена"
                        wire:click="cancelReportSettings"
                        x-on:click="$dispatch('modal-hide', { name: 'reports-report-settings-modal' })"
                    />
                </div>
            </div>
        </x-slot>
    </x-overlay.modal>

    @if ($this->canDeleteReports)
        <x-overlay.modal name="report-delete-confirm" title="Вы уверены?">
            <x-slot:body>
                <div x-data="{ id: null }"
                    x-on:report-delete-ask.window="id = $event.detail.id; $dispatch('modal-show', { name: 'report-delete-confirm' })">
                    <p class="text-primary-text">Отчет будет удален.</p>
                    <div class="mt-6 flex justify-between gap-4">
                        <x-button.button icon="icons.delete" label="Удалить" variant="primary"
                            x-on:click="$wire.delete(id); $dispatch('modal-hide', { name: 'report-delete-confirm' })" />
                        <x-button.button label="Отменить"
                            x-on:click="$dispatch('modal-hide', { name: 'report-delete-confirm' })" />
                    </div>
                </div>
            </x-slot>
        </x-overlay.modal>
    @endif
</div>