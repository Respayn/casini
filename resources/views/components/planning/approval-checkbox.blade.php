@props([
    'canEdit' => false,
])

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
