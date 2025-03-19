@props([
    'items' => [],
])

@php
    $currentRoute = Route::currentRouteName();
@endphp

<div
    class="flex gap-2.5"
    {{ $attributes }}
>
    @foreach ($items as $item)
        <x-button.button
            :href="$item['route'] === $currentRoute ? '' : route($item['route'])"
            :variant="$item['route'] === $currentRoute ? 'primary' : 'outlined'"
            label="{{ $item['label'] }}"
            @class([
                'hover:bg-primary hover:text-white' => $item['route'] !== $currentRoute,
                'hover:!bg-primary hover:!text-white' => $item['route'] === $currentRoute
            ])
        ></x-button.button>
    @endforeach
</div>
