@php
    $filteredProject = $this->sidebarFilteredProject;
@endphp

@if ($filteredProject)
    <div
        class="border-primary mb-4 flex flex-wrap items-center justify-between gap-3 break-words rounded-lg border bg-blue-50 p-4 text-sm text-primary-text"
        wire:key="sidebar-filter-hint-{{ $filteredProject['id'] }}"
    >
        <div>
            Показан клиенто-проект:
            <span class="font-semibold">{{ $filteredProject['name'] }}</span>
            <span class="text-input-text">(№{{ $filteredProject['id'] }})</span>
        </div>

        <x-button.button
            variant="link"
            label="Сбросить фильтр"
            wire:click="clearSidebarProjectFilter"
        />
    </div>
@endif
