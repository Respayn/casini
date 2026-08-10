@props([
    'canEdit' => false,
    'date' => null,
    'approvedByName' => null,
])

<div class="flex flex-col items-center justify-center gap-1">
    @if ($canEdit)
        <x-form.checkbox {{ $attributes }} />
    @else
        <div
            class="relative inline-block"
            x-data="{ open: false }"
        >
            <span
                x-ref="approvalDeniedTrigger"
                @mouseenter="open = true"
                @mouseleave="open = false"
            >
                <x-form.checkbox
                    disabled
                    {{ $attributes }}
                />
            </span>
            <template x-teleport="body">
                <div
                    class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                    style="z-index: 1000"
                    x-show="open"
                    x-cloak
                    x-anchor.bottom="$refs.approvalDeniedTrigger"
                >
                    {{ __('permissions.denied') }}
                </div>
            </template>
        </div>
    @endif

    @if (filled($date))
        <span
            @class([
                'mt-0.5 text-xs italic leading-none',
                'cursor-default' => filled($approvedByName),
            ])
            style="color: #BFD9FF"
            @if (filled($approvedByName))
                x-data="{ open: false }"
                x-ref="approvalDateTrigger"
                @mouseenter="open = true"
                @mouseleave="open = false"
            @endif
        >
            {{ $date }}
            @if (filled($approvedByName))
                <template x-teleport="body">
                    <div
                        class="rounded-md bg-gray-700 p-2 text-sm italic text-white"
                        style="z-index: 1000; max-width: 16rem"
                        x-show="open"
                        x-cloak
                        x-anchor.top="$refs.approvalDateTrigger"
                    >
                        Согласовал {{ $approvedByName }}
                    </div>
                </template>
            @endif
        </span>
    @endif
</div>
