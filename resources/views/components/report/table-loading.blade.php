@props([
    /**
     * Список Livewire-целей через запятую (свойства / методы), при которых показываем скелетон.
     * Не включать saveSettingsSnapshot — иначе мигание при открытии шестерёнки.
     * Не включать refreshAllData / pollRefreshProgress — ручной съём рисует скелет по строкам.
     */
    'targets' => 'queryData.showInactive, queryData.includeVat, queryData.dateFrom, queryData.dateTo, applySettingsSnapshot',
])

@php
    $targets = is_array($targets) ? implode(', ', $targets) : (string) $targets;
@endphp

{{-- Оверлей только под настоящей шапкой; шапку не приглушаем --}}
<div
    {{ $attributes->class(['relative mt-3'])->merge([
        'style' => 'min-height: 240px; '.$attributes->get('style'),
    ]) }}
    x-data="{
        headerOffset: 0,
        syncHeaderOffset() {
            const thead = this.$refs.host?.querySelector('thead');
            this.headerOffset = thead ? Math.ceil(thead.getBoundingClientRect().height) : 0;
        },
        init() {
            this.$nextTick(() => {
                this.syncHeaderOffset();
                if (typeof ResizeObserver === 'undefined' || ! this.$refs.host) {
                    return;
                }
                this._ro = new ResizeObserver(() => this.syncHeaderOffset());
                this._ro.observe(this.$refs.host);
            });
        },
        destroy() {
            this._ro?.disconnect();
        }
    }"
>
    <div
        wire:loading
        wire:target="{{ $targets }}"
        class="pointer-events-none absolute inset-0 z-10 overflow-hidden"
        x-bind:style="'top: ' + headerOffset + 'px; background-color: rgba(255, 255, 255, 0.75)'"
    >
        <x-report.table-skeleton style="min-height: 100%" />
    </div>

    <div x-ref="host">
        {{ $slot }}
    </div>
</div>
