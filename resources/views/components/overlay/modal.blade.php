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
    <div @class([
        'min-w-1/4 relative inset-0 m-auto flex max-w-full',
        'w-fit' => ! empty($sidebar),
    ])>
        <div @class([
            'rounded-2xl' => empty($sidebar),
            'rounded-l-2xl' => ! empty($sidebar),
            'bg-white p-6 flex flex-col',
            'flex-1' => empty($sidebar),
            'w-fit shrink-0 min-w-0' => ! empty($sidebar),
        ]) style="max-width: 800px">
            <div class="mb-7 flex items-center justify-between gap-4">
                <div class="flex min-w-0 flex-1 items-center gap-4">
                    <span class="text-primary-text text-2xl font-semibold">
                        {{ $title }}
                    </span>
                    @isset($titleActions)
                        {{ $titleActions }}
                    @endisset
                </div>
                <span
                    class="text-secondary-text shrink-0 cursor-pointer"
                    x-on:click="show = false"
                >
                    Закрыть
                </span>
            </div>
            <div @class([
                'flex-1' => empty($sidebar),
                'min-w-0 w-fit' => ! empty($sidebar),
            ])>{{ $body }}</div>
        </div>
        @if (! empty($sidebar))
            <div class="bg-modal-sidebar-background w-[543px] max-w-[543px] shrink-0 rounded-r-2xl p-6">{{ $sidebar }}</div>
        @endif
    </div>
</div>
