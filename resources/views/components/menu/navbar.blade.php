@props([
    'previous' => null,
    'after' => null,
    'items' => [],
])

@php
    $currentRoute = Route::currentRouteName();
    $deniedMessage = __('permissions.denied');
@endphp

<div class="flex gap-2.5" {{ $attributes }}>
    {{ $previous }}

    @foreach ($items as $item)
        @php
            $routeValue = $item['route'];
            $isArrayRoute = is_array($routeValue);
            $routeName = $isArrayRoute ? $routeValue[0] : $routeValue;
            $routeParams = $isArrayRoute ? array_slice($routeValue, 1) : [];
            $canAccess = $item['canAccess'] ?? true;
            $isActive = $canAccess && $currentRoute === $routeName;
            $routeHref = $isActive ? '' : ($isArrayRoute ? route($routeName, ...$routeParams) : route($routeName));
        @endphp

        @if ($canAccess)
            <x-button.button
                :href="$routeHref"
                :variant="$isActive ? 'primary' : 'outlined'"
                label="{{ $item['label'] }}"
                @class([
                    'hover:bg-primary hover:text-white' => !$isActive,
                    'hover:!bg-primary hover:!text-white' => $isActive,
                ])
            />
        @else
            <div
                class="relative inline-block"
                x-data="{ open: false }"
            >
                <span
                    x-ref="navTrigger"
                    @mouseenter="open = true"
                    @mouseleave="open = false"
                >
                    <x-button.button
                        variant="outlined"
                        label="{{ $item['label'] }}"
                        disabled
                        class="opacity-50 cursor-not-allowed"
                    />
                </span>
                <template x-teleport="body">
                    <div
                        class="w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                        style="z-index: 1000"
                        x-show="open"
                        x-cloak
                        x-anchor.bottom="$refs.navTrigger"
                    >
                        {{ $deniedMessage }}
                    </div>
                </template>
            </div>
        @endif
    @endforeach

    {{ $after }}
</div>
