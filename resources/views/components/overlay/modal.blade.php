@props([
    'title' => '',
    'name',
])

<div
    class="fixed inset-0 z-50 flex backdrop-blur-[5px]"
    x-data="{ show: false, name: '{{ $name }}' }"
    x-show="show"
    x-on:modal-show.window="if ($event.detail.name !== name) return; show = true;"
    x-on:modal-hide.window="if ($event.detail.name !== name) return; show = false;"
    x-on:keydown.escape.window="show = false"
    x-cloak
    x-transition
>
    <div
        class="fixed inset-0 bg-modal-backdrop opacity-5"
        x-on:click="show = false"
    ></div>
    <div class="relative inset-0 max-w-3xl p-6 m-auto bg-white rounded-2xl min-w-1/4">
        <div class="flex items-baseline justify-between mb-7">
            <span class="text-2xl font-semibold text-primary-text">
                {{ $title }}
            </span>
            <span
                class="cursor-pointer text-secondary-text"
                x-on:click="show = false"
            >Закрыть</span>
        </div>
        <div>{{ $body }}</div>
    </div>
</div>
