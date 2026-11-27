@php
    $filteredProject = $this->sidebarFilteredProject;
    $lockSidebarFilter = method_exists($this, 'isManualReportDataRefreshing')
        && $this->isManualReportDataRefreshing();
    $lockSidebarFilterTitle = 'Пока идёт обновление данных, неактивные, НДС, период, фильтр и клиенто-проект менять нельзя';
@endphp

@if ($filteredProject)
    <div
        class="border-primary mb-4 flex flex-wrap items-center justify-between gap-3 break-words rounded-lg border bg-blue-50 p-4 text-sm text-primary-text"
        wire:key="sidebar-filter-hint-{{ $filteredProject['id'] }}"
        @if ($lockSidebarFilter) title="{{ $lockSidebarFilterTitle }}" @endif
    >
        <div>
            Показан клиенто-проект:
            <span class="font-semibold">{{ $filteredProject['name'] }}</span>
            <span class="text-input-text">(№{{ $filteredProject['id'] }})</span>
        </div>

        <x-button.button
            variant="secondary"
            icon="icons.filter-alt-off"
            label="Сбросить фильтр"
            wire:click="clearSidebarProjectFilter"
            :disabled="$lockSidebarFilter"
        />
    </div>
@endif
