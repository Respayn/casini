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
        dateOpen: false,
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
                if (! approval) {
                    return;
                }
                this.approved = !! approval.approved;
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

    <span
        x-show="date"
        x-cloak
        @class([
            'mt-0.5 text-xs italic leading-none',
            'cursor-default' => filled($approvedByName),
        ])
        style="color: #BFD9FF"
        x-ref="approvalDateTrigger"
        x-on:mouseenter="if (approvedByName) dateOpen = true"
        x-on:mouseleave="dateOpen = false"
    >
        <span x-text="date">{{ $date }}</span>
        <template x-teleport="body">
            <div
                class="rounded-md bg-gray-700 p-2 text-sm italic text-white"
                style="z-index: 1000; max-width: 16rem"
                x-show="approvedByName && dateOpen"
                x-cloak
                x-anchor.top="$refs.approvalDateTrigger"
            >
                Согласовал <span x-text="approvedByName">{{ $approvedByName }}</span>
            </div>
        </template>
    </span>
</div>
