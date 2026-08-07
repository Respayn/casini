@props([
    'columnModal' => 'column-settings-modal',
    'lastRefreshLabel' => null,
])

@php
    $refreshTooltip = $lastRefreshLabel
        ? 'Последнее обновление данных: '.$lastRefreshLabel
        : 'Последнее обновление данных: ещё не обновлялось';
@endphp

<div {{ $attributes->class(['ml-auto flex flex-wrap items-center gap-2']) }}>
    <x-overlay.modal-trigger :name="$columnModal" wire:click="saveSettingsSnapshot">
        <x-button.button
            icon="icons.columns"
            variant="link"
            title="Настроить столбцы"
        />
    </x-overlay.modal-trigger>

    <div
        class="relative inline-flex"
        x-data="{ open: false }"
    >
        <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-primary-text transition hover:text-primary disabled:cursor-not-allowed disabled:opacity-60"
            wire:click="refreshAllData"
            wire:loading.attr="disabled"
            wire:target="refreshAllData"
            x-ref="refreshTrigger"
            @mouseenter="open = true"
            @mouseleave="open = false"
            aria-label="Обновить данные"
        >
            <x-icons.refresh-data
                wire:loading.remove
                wire:target="refreshAllData"
                class="h-[18px] w-[18px]"
            />
            <x-icons.refresh-data
                wire:loading
                wire:target="refreshAllData"
                class="h-[18px] w-[18px] animate-spin"
            />
        </button>

        <template x-teleport="body">
            <div
                class="w-72 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                style="z-index: 1000"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-cloak
                x-anchor.top="$refs.refreshTrigger"
            >
                {{ $refreshTooltip }}
            </div>
        </template>
    </div>
</div>
