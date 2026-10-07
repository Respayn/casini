@props([
    'class' => '',
    'panelClass' => '',
    'panelMaxWidth' => '16rem',
])

@php
    $hasCustomTrigger = isset($trigger) && ! $trigger->isEmpty();
@endphp

<div
    @class([
        'relative inline-block',
        'cursor-pointer' => ! $hasCustomTrigger,
    ])
    x-data="{ open: false }"
>
    <span
        @class([
            'tooltip-icon' => ! $hasCustomTrigger,
        ])
        @mouseenter="open = true"
        @mouseleave="open = false"
        x-ref="icon"
    >
        @if ($hasCustomTrigger)
            {{ $trigger }}
        @else
            <x-icons.tooltip class="{{ $class }} text-white" />
        @endif
    </span>

    <template x-teleport="body">
        <div
            @class([
                'rounded-md bg-gray-700 p-2 text-sm italic text-white whitespace-normal break-words',
                $panelClass,
            ])
            style="z-index: 1000; max-width: min({{ $panelMaxWidth }}, calc(100vw - 2rem)); width: max-content;"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            x-anchor.top="$refs.icon"
        >
            {{ $slot }}
        </div>
    </template>
</div>
