<div x-data x-init="
        @if($pendingDownloadId)
            $nextTick(() => $wire.download({{ $pendingDownloadId }}))
        @endif
    ">
    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Отчеты</h1>
        <div>
            <x-button.button href="{{ route('reports.create') }}" icon="icons.plus" label="Создать отчет"
                variant="primary" />
            <x-button.button href="{{ route('templates') }}" label="Шаблоны отчетов" variant="primary" />
        </div>
    </div>

    {{-- Фильтры --}}
    <div class="flex items-center">
        <div class="mr-3.5">
            <label>Неактивные клиенто-проекты:</label>
            <x-form.checkbox wire:model.live="showInactiveProjects" />
        </div>

        <div class="flex gap-2">
            <x-form.month-picker wire:model.live="periodFrom" />
            <x-form.month-picker wire:model.live="periodTo" />
        </div>

        <div class="flex-end ml-auto">
            <x-overlay.modal-trigger name="column-settings-modal">
                <x-button.button icon="icons.edit" label="Настроить столбцы" variant="link" />
            </x-overlay.modal-trigger>
        </div>
    </div>

    @if (empty($this->reports))
        <div class="mt-20 flex flex-col items-center gap-4">
            <span class="text-caption-text">Нет отчетов для отображения</span>
            <div>
                <x-button.button href="{{ route('system-settings.clients-and-projects') }}" target="_blank"
                    icon="icons.plus" label="Добавить клиенто-проект" variant="primary" />
            </div>
        </div>
    @else
        <div class="mt-3">
            <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                <x-data.table>
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
                                            <x-data.table-cell class="bg-primary text-white cursor-pointer"
                                                wire:click="download({{ $report->reportId }})">
                                                <div class="flex justify-center">
                                                    <x-icons.download />
                                                </div>
                                            </x-data.table-cell>
                                        @break

                                        @case('is_ready')
                                            <x-data.table-cell>
                                                <div class="flex justify-center">
                                                    <x-form.checkbox checked="{{ $report->isReady }}" disabled
                                                        wire:change.renderless="updateIsReady({{ $report->reportId }}, $event.target.checked)" />
                                                </div>
                                            </x-data.table-cell>
                                        @break

                                        @case('is_accepted')
                                            <x-data.table-cell>
                                                <div class="flex justify-center">
                                                    <x-form.checkbox checked="{{ $report->isAccepted }}"
                                                        wire:change.renderless="updateIsAccepted({{ $report->reportId }}, $event.target.checked)" />
                                                </div>
                                            </x-data.table-cell>
                                        @break

                                        @case('is_sent')
                                            <x-data.table-cell>
                                                <div class="flex justify-center">
                                                    <x-form.checkbox checked="{{ $report->isSent }}"
                                                        wire:change.renderless="updateIsSent({{ $report->reportId }}, $event.target.checked)" />
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
</div>