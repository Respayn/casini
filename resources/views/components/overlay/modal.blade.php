@props([
    'title' => '',
    'name',
    'body' => null,
    'sidebar' => null,
])

<div
    class="modal fixed inset-0 z-50 flex backdrop-blur-[5px]"
    x-bind:class="{ 'open': show }"
    x-data="{ show: false, name: '{{ $name }}' }"
    x-show="show"
    x-on:modal-show.window="if ($event.detail.name !== name) return; show = true;"
    x-on:modal-hide.window="if ($event.detail.name !== name) return; show = false;"
    x-on:keydown.escape.window="show = false"
    x-cloak
    x-transition
>
    <div
        class="bg-modal-backdrop fixed inset-0 opacity-5"
        x-on:click="show = false"
    ></div>
    <div class="min-w-1/4 relative inset-0 m-auto flex max-w-full">
        <div @class([
            'rounded-2xl' => empty($sidebar),
            'rounded-l-2xl' => !empty($sidebar),
            'bg-white p-6 flex flex-col flex-1',
        ])>
            <div class="mb-7 flex items-baseline justify-between">
                <span class="text-primary-text text-2xl font-semibold">
                    {{ $title }}
                </span>
                <span
                    class="text-secondary-text cursor-pointer"
                    x-on:click="show = false"
                >
                    Закрыть
                </span>
            </div>
            <div class="flex-1">{{ $body }}</div>
        </div>
        @if (!empty($sidebar))
            <div class="bg-modal-sidebar-background max-w-[543px] flex-1 rounded-r-2xl p-6">{{ $sidebar }}</div>
        @endif
    </div>
</div>
