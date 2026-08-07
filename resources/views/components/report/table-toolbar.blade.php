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
        @mouseenter="open = true"
        @mouseleave="open = false"
    >
        <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-primary-text transition hover:text-primary disabled:cursor-not-allowed disabled:opacity-60"
            wire:click="refreshAllData"
            wire:loading.attr="disabled"
            wire:target="refreshAllData"
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

        {{-- Inline styles: avoid Alpine :style (it overwrote position) and group-hover utilities missing from staging build. --}}
        <div
            x-show="open"
            x-cloak
            role="tooltip"
            class="pointer-events-none"
            style="position: absolute; right: 0; bottom: calc(100% + 0.5rem); z-index: 9999; width: max-content; max-width: min(20rem, calc(100vw - 1.5rem)); border-radius: 0.375rem; background: #374151; padding: 0.5rem 0.75rem; text-align: left; font-size: 0.875rem; line-height: 1.375; color: #fff; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);"
        >
            {{ $refreshTooltip }}
        </div>
    </div>
</div>
