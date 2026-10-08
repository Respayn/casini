@props([
    'canEdit' => false,
    'date' => null,
    'approvedByName' => null,
    'approved' => false,
    'rowIndex' => null,
    'quarter' => null,
])

<div
    class="flex flex-col items-center justify-center gap-1"
    x-data="{
        date: @js($date),
        approvedByName: @js($approvedByName),
        approved: @js((bool) $approved),
        rowIndex: {{ $rowIndex === null ? 'null' : (int) $rowIndex }},
        quarter: {{ $quarter === null ? 'null' : (int) $quarter }},
        deniedOpen: false,
        _syncHandler: null,

        init() {
            this._syncHandler = () => {
                if (this.rowIndex === null || this.quarter === null) {
                    return;
                }
                const parent = window.Livewire
                    ? window.Livewire.all().find((component) => component.name === 'pages::planning')
                    : null;
                const tableData = parent && parent.$wire ? parent.$wire.tableData : null;
                const row = tableData ? tableData[this.rowIndex] : null;
                const approval = row && row.approvals ? row.approvals[this.quarter] : null;
                if (! approval || ! approval.approved) {
                    this.approved = false;
                    this.date = null;
                    this.approvedByName = null;
                    return;
                }
                this.approved = true;
                this.date = approval.date || null;
                this.approvedByName = approval.approved_by_name || null;
            };
            window.addEventListener('planning-table-sync', this._syncHandler);
        },

        destroy() {
            if (this._syncHandler) {
                window.removeEventListener('planning-table-sync', this._syncHandler);
            }
        },
    }"
>
    @if ($canEdit)
        <x-form.checkbox
            {{ $attributes }}
            x-bind:checked="approved"
        />
    @else
        <div class="relative inline-block">
            <span
                x-ref="approvalDeniedTrigger"
                @mouseenter="deniedOpen = true"
                @mouseleave="deniedOpen = false"
            >
                <x-form.checkbox
                    disabled
                    {{ $attributes }}
                    x-bind:checked="approved"
                />
            </span>
            <template x-teleport="body">
                <div
                    class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                    style="z-index: 1000"
                    x-show="deniedOpen"
                    x-cloak
                    x-anchor.bottom="$refs.approvalDeniedTrigger"
                >
                    {{ __('permissions.denied') }}
                </div>
            </template>
        </div>
    @endif

    {{-- Дата без ФИО — просто текст --}}
    <span
        x-show="date && !approvedByName"
        x-cloak
        class="mt-0.5 text-xs italic leading-none"
        style="color: #BFD9FF"
        x-text="date"
    >{{ $date }}</span>

    {{-- Дата с ФИО — готовое облачко, наведение на саму дату (без иконки «?») --}}
    <span x-show="date && approvedByName" x-cloak class="mt-0.5">
        <x-overlay.tooltip>
            <x-slot:trigger>
                <span
                    class="cursor-default text-xs italic leading-none"
                    style="color: #BFD9FF"
                    x-text="date"
                >{{ $date }}</span>
            </x-slot:trigger>
            Согласовал <span x-text="approvedByName">{{ $approvedByName }}</span>
        </x-overlay.tooltip>
    </span>
</div>
