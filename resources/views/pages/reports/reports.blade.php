<div x-data x-init="
        @if($pendingDownloadId)
            $nextTick(() => $wire.download({{ $pendingDownloadId }}))
        @endif
    ">
    <x-layout.sidebar-filter-hint />

    @foreach ($generationWarnings as $warning)
        <x-feedback.notice variant="error">{{ $warning }}</x-feedback.notice>
    @endforeach

    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Отчеты</h1>
        <div>
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
    <div class="flex flex-wrap items-center gap-y-3">
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
        <div class="mt-3">
            <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                <x-data.table class="w-full">
                    <x-data.table-columns>
                        @foreach ($this->visibleColumns as $column)
                            <x-data.table-column>{{ $column['label'] }}</x-data.table-column>
                        @endforeach
                    </x-data.table-columns>
                    <x-data.table-rows>
                        @foreach ($this->reports as $report)
                            <x-data.table-row>
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
                                            <x-data.table-cell>{{ $report->channel }}</x-data.table-cell>
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
                                                        wire:loading.attr="disabled"
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
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.scroll-panel>
        </div>
    @endif

    <x-column-settings-modal :columns="$columnSettings" />

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