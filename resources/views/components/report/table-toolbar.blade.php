@props([
    'columnModal' => 'column-settings-modal',
    'lastRefreshLabel' => null,
    'refreshable' => true,
    'columnTriggerAction' => 'saveSettingsSnapshot',
    'confirmUnfilteredRefresh' => false,
])

@php
    $refreshTooltip = $lastRefreshLabel
        ? 'Последнее обновление данных: '.$lastRefreshLabel
        : 'Последнее обновление данных: ещё не обновлялось';

    $tooltipStyle = 'position: absolute; right: 0; bottom: calc(100% + 0.5rem); z-index: 9999; width: max-content; max-width: min(20rem, calc(100vw - 1.5rem)); border-radius: 0.375rem; background: #374151; padding: 0.5rem 0.75rem; text-align: left; font-size: 0.875rem; line-height: 1.375; color: #fff; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);';
    $refreshTooltipStyle = 'position: absolute; right: calc(100% + 0.5rem); top: 50%; transform: translateY(-50%); z-index: 9999; width: max-content; max-width: min(16rem, calc(100vw - 1.5rem)); border-radius: 0.375rem; background: #374151; padding: 0.5rem 0.75rem; text-align: left; font-size: 0.875rem; line-height: 1.375; color: #fff; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); white-space: normal;';
@endphp

<div {{ $attributes->class(['ml-auto flex flex-wrap items-center']) }}>
    <div
        class="relative inline-flex"
        x-data="{ open: false }"
        @mouseenter="open = true"
        @mouseleave="open = false"
    >
        <x-overlay.modal-trigger :name="$columnModal" :wire:click="$columnTriggerAction">
            <x-button.button
                icon="icons.columns"
                variant="link"
                size="sm"
                aria-label="Настроить колонки"
            />
        </x-overlay.modal-trigger>

        {{-- Inline styles: avoid Alpine :style (it overwrote position) and group-hover utilities missing from staging build. --}}
        <div
            x-show="open"
            x-cloak
            role="tooltip"
            class="pointer-events-none"
            style="{{ $tooltipStyle }}"
        >
            Настроить колонки
        </div>
    </div>

    @if ($refreshable)
    <div
        class="relative inline-flex"
        x-data="{
            open: false,
            elapsed: '0:00',
            timer: null,
            lastStepAt: null,
            init() {
                this.timer = setInterval(() => this.tick(), 1000);
                this.tick();
            },
            destroy() {
                if (this.timer) {
                    clearInterval(this.timer);
                }
            },
            refreshing() {
                return !! $wire.isReportDataRefreshing;
            },
            label() {
                return $wire.refreshProgressLabel || 'Обновление…';
            },
            secondLine() {
                if (! this.refreshing()) {
                    return '';
                }
                const total = Number($wire.refreshTotal || 0);
                const index = Number($wire.refreshCurrentIndex || 0);
                if (total > 1 && index > 0) {
                    return index + ' из ' + total + ', ' + this.elapsed;
                }
                return this.elapsed;
            },
            tick() {
                if (! this.refreshing()) {
                    this.elapsed = '0:00';
                    this.lastStepAt = null;
                    return;
                }
                const startedAt = Number($wire.refreshStepStartedAt || 0);
                if (! startedAt) {
                    this.elapsed = '0:00';
                    return;
                }
                if (this.lastStepAt !== startedAt) {
                    this.lastStepAt = startedAt;
                }
                const seconds = Math.max(0, Math.floor(Date.now() / 1000) - startedAt);
                const minutes = Math.floor(seconds / 60);
                const rest = String(seconds % 60).padStart(2, '0');
                this.elapsed = minutes + ':' + rest;
            }
        }"
        x-on:mouseenter="if (! refreshing()) open = true"
        x-on:mouseleave="if (! refreshing()) open = false"
    >
        <button
            type="button"
            class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-md text-primary-text transition hover:text-primary disabled:cursor-not-allowed disabled:opacity-60"
            x-bind:disabled="refreshing()"
            aria-label="Обновить данные"
            @if ($confirmUnfilteredRefresh)
                x-on:click="if (! refreshing()) $dispatch('modal-show', { name: 'refresh-all-projects-confirm' })"
            @else
                x-on:click="if (! refreshing()) $wire.refreshAllData()"
            @endif
        >
            <span x-show="! refreshing()">
                <x-icons.refresh-data class="h-[18px] w-[18px]" />
            </span>
            <span x-show="refreshing()" x-cloak>
                <x-icons.refresh-data class="h-[18px] w-[18px] animate-spin" />
            </span>
        </button>

        <div
            x-show="refreshing() || open"
            x-cloak
            role="tooltip"
            class="pointer-events-none"
            style="{{ $refreshTooltipStyle }}"
        >
            <div x-show="refreshing()" class="leading-snug whitespace-nowrap">
                <div x-text="label()"></div>
                <div x-text="secondLine()"></div>
            </div>
            <div x-show="! refreshing()">
                {{ $refreshTooltip }}
            </div>
        </div>
    </div>
    @endif
</div>
