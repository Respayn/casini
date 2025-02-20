<div
    class="flex items-center justify-between cursor-pointer"
    x-on:click="isOpen = !isOpen"
>
    <div class="text-[18px] font-semibold">
        {{ $slot }}
    </div>
    <div>
        <x-icons.accordion-arrow x-bind:class="{ 'rotate-180': isOpen }" />
    </div>
</div>
