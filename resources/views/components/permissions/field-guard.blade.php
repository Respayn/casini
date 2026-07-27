@props([
    'enabled' => true,
    'message' => null,
])

@php
    $message ??= __('permissions.denied');
@endphp

@if ($enabled)
    {{ $slot }}
@else
    <div
        class="relative inline-block"
        x-data="{ open: false }"
    >
        <span
            x-ref="fieldGuardTrigger"
            @mouseenter="open = true"
            @mouseleave="open = false"
        >
            {{ $slot }}
        </span>
        <template x-teleport="body">
            <div
                class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                style="z-index: 1000"
                x-show="open"
                x-cloak
                x-anchor.bottom="$refs.fieldGuardTrigger"
            >
                {{ $message }}
            </div>
        </template>
    </div>
@endif
