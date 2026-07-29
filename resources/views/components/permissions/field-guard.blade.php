@props([
    'enabled' => true,
    'message' => null,
    'anchor' => 'bottom',
    'fill' => true,
])

@php
    $message ??= __('permissions.denied');
@endphp

@if ($enabled)
    {{ $slot }}
@else
    <div
        {{ $attributes->class([
            'relative',
            'block w-full' => $fill,
            'inline-flex w-auto' => ! $fill,
        ]) }}
        x-data="{ open: false }"
    >
        <span
            @class([
                'block w-full' => $fill,
                'inline-flex' => ! $fill,
            ])
            x-ref="fieldGuardTrigger"
            @mouseenter="open = true"
            @mouseleave="open = false"
        >
            {{ $slot }}
        </span>
        <template x-teleport="body">
            <div
                class="z-1000 w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                style="z-index: 1000"
                x-show="open"
                x-cloak
                x-anchor.{{ $anchor }}="$refs.fieldGuardTrigger"
            >
                {{ $message }}
            </div>
        </template>
    </div>
@endif
