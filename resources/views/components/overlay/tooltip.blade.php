@props(['class' => ''])

<div x-data="{ open: false }" class="relative inline-block cursor-pointer">
    <span @mouseenter="open = true" @mouseleave="open = false" class="tooltip-icon">
        <x-icons.tooltip class="{{ $class }}" />
    </span>
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute left-full bottom-full mt-1 w-64 bg-gray-700 text-white text-sm italic rounded-md p-2 z-10"
         x-cloak>
        {{ $slot }}
    </div>
</div>
