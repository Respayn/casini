@props([
    'title' => '',
    'name',
    'body' => null,
    'sidebar' => null,
    'icon' => null,
    'collapsibleSidebar' => false,
    'stacked' => false,
])

@php
    $hasSidebar = ! empty($sidebar);
    $collapsibleRequested = (bool) $collapsibleSidebar;
    $stacked = (bool) $stacked;
@endphp

<div
    @class([
        'modal fixed inset-0 flex items-center justify-center backdrop-blur-[5px]',
        'z-50' => ! $stacked,
    ])
    @if ($stacked)
        style="z-index: 60"
    @endif
    wire:ignore.self
    x-bind:class="{ 'open': show }"
    x-data="{
        show: false,
        name: @js($name),
        sidebarFits: true,
        collapsible: @js($collapsibleRequested),
        checkSidebarFit() {
            if (! this.collapsible) {
                this.sidebarFits = true;

                return;
            }

            // Форма до 800px + колонка 543px + отступы от краёв экрана.
            this.sidebarFits = window.innerWidth >= (800 + 543 + 48);
        },
    }"
    x-show="show"
    x-on:modal-show.window="if ($event.detail.name !== name) return; show = true; $nextTick(() => checkSidebarFit())"
    x-on:modal-hide.window="if ($event.detail.name !== name) return; show = false"
    @if ($stacked)
        x-on:keydown.escape.window.capture="if (show) { show = false; $event.stopImmediatePropagation() }"
    @else
        x-on:keydown.escape.window="if (show) show = false"
    @endif
    x-on:resize.window.debounce.100ms="if (show) checkSidebarFit()"
    x-cloak
    x-transition
>
    <div
        class="bg-modal-backdrop fixed inset-0 opacity-5"
        x-on:click="show = false"
    ></div>
    <div
        class="relative flex"
        @if ($hasSidebar && $collapsibleRequested)
            {{-- max-w-[800px] нет в собранном CSS на staging: лимит только через style --}}
            x-bind:class="sidebarFits ? 'w-fit max-w-full' : 'w-full'"
            x-bind:style="sidebarFits ? {} : { maxWidth: '800px' }"
        @elseif ($hasSidebar)
            @class(['w-fit max-w-full'])
        @else
            @class(['max-w-full'])
        @endif
    >
        <div
            class="flex min-w-0 flex-col bg-white p-6"
            @if ($hasSidebar && $collapsibleRequested)
                x-bind:class="sidebarFits ? 'w-fit shrink-0 rounded-l-2xl' : 'w-full rounded-2xl'"
            @else
                @class([
                    'flex-1 rounded-2xl' => ! $hasSidebar,
                    'w-fit shrink-0 rounded-l-2xl' => $hasSidebar,
                ])
            @endif
            @if (! ($hasSidebar && $collapsibleRequested))
                style="max-width: 800px"
            @endif
        >
            <div class="mb-7 flex items-center justify-between gap-4">
                <div class="flex min-w-0 flex-1 items-center gap-4">
                    @if ($icon)
                        <span wire:key="modal-icon-{{ $icon }}-{{ $name }}">
                            <x-dynamic-component :component="$icon" class="size-7 shrink-0" />
                        </span>
                    @endif
                    <span class="text-primary-text text-2xl font-semibold">
                        {{ $title }}
                    </span>
                    @if ($collapsibleRequested && $hasSidebar)
                        <span x-show="! sidebarFits" x-cloak>
                            <x-button.button
                                label="Инструкция"
                                x-on:click="$dispatch('modal-show', { name: 'integration-instruction-modal' })"
                            />
                        </span>
                    @endif
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
            <div
                @if ($hasSidebar && $collapsibleRequested)
                    x-bind:class="sidebarFits ? 'min-w-0 w-fit' : 'min-w-0 w-full [&>div]:!w-full [&>div>*]:!w-full [&_.scrollpanel]:!w-full [&_form]:!w-full'"
                @else
                    @class([
                        'flex-1' => ! $hasSidebar,
                        'min-w-0 w-fit' => $hasSidebar,
                    ])
                @endif
            >{{ $body }}</div>
        </div>
        @if ($hasSidebar)
            <div
                class="bg-modal-sidebar-background w-[543px] max-w-[543px] shrink-0 rounded-r-2xl p-6"
                @if ($collapsibleRequested)
                    x-show="sidebarFits"
                    x-cloak
                @endif
            >{{ $sidebar }}</div>
        @endif
    </div>
</div>
