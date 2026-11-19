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
@endphp

<div {{ $attributes->class(['ml-auto flex flex-wrap items-center']) }}>
    <x-overlay.tooltip panel-class="not-italic" panel-max-width="16rem">
        <x-slot:trigger>
            <x-overlay.modal-trigger :name="$columnModal" :wire:click="$columnTriggerAction">
                <x-button.button
                    icon="icons.columns"
                    variant="link"
                    size="sm"
                    aria-label="Настроить колонки"
                />
            </x-overlay.modal-trigger>
        </x-slot:trigger>
        Настроить колонки
    </x-overlay.tooltip>

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
            automaticMode() {
                return !! $wire.isAutomaticDataRefresh;
            },
            modeLine() {
                return this.automaticMode() ? 'Автоматический съём данных' : '';
            },
            projectLine() {
                return $wire.refreshProgressProject || '';
            },
            sourceLine() {
                if ($wire.refreshProgressLabel) {
                    return $wire.refreshProgressLabel;
                }

                return this.automaticMode() ? '' : 'Обновление…';
            },
            counterLine() {
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
            x-ref="refreshButton"
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

        <template x-teleport="body">
            <div
                x-show="refreshing() || open"
                x-cloak
                x-anchor.left.offset.6="$refs.refreshButton"
                role="tooltip"
                class="pointer-events-none z-[9999] w-max max-w-[min(16rem,calc(100vw-1.5rem))] rounded-md bg-gray-700 px-3 py-2 text-left text-sm leading-snug text-white shadow-lg"
            >
                <div x-show="refreshing()" class="leading-snug">
                    <div x-show="modeLine()" x-text="modeLine()" class="break-words"></div>
                    <div x-show="projectLine()" x-text="projectLine()" class="break-words"></div>
                    <div x-show="sourceLine()" x-text="sourceLine()"></div>
                    <div x-show="counterLine()" x-text="counterLine()"></div>
                </div>
                <div x-show="! refreshing()">
                    {{ $refreshTooltip }}
                </div>
            </div>
        </template>
    </div>
    @endif
</div>
