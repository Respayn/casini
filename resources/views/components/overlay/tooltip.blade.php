@props(['class' => ''])

<div
    class="relative inline-block cursor-pointer"
    x-data="{ open: false }"
>
    <span
        class="tooltip-icon"
        @mouseenter="open = true"
        @mouseleave="open = false"
        x-ref="icon"
    >
        <x-icons.tooltip class="{{ $class }} text-white" />
    </span>

    <template x-teleport="body">
        <div
            class="z-10 w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
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
