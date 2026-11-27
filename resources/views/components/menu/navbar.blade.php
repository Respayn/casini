@props([
    'previous' => null,
    'after' => null,
    'items' => [],
    // h-full + items-stretch: в узком ряду короткие подписи («Справочники»)
    // совпадают по высоте с теми, что переносятся на 2 строки
    'itemClass' => 'flex h-full min-h-10 items-center justify-center rounded-lg px-3.5 py-2 text-center leading-5',
    'itemStyle' => null,
    'align' => 'stretch',
])

@php
    $currentRoute = Route::currentRouteName();
    $deniedMessage = __('permissions.denied');
    $alignClass = match ($align) {
        'center' => 'items-center',
        'start' => 'items-start',
        default => 'items-stretch',
    };
@endphp

<div class="flex gap-2.5 {{ $alignClass }}" {{ $attributes }}>
    {{ $previous }}

    @foreach ($items as $item)
        @php
            $routeValue = $item['route'];
            $isArrayRoute = is_array($routeValue);
            $routeName = $isArrayRoute ? $routeValue[0] : $routeValue;
            $routeParams = $isArrayRoute ? array_slice($routeValue, 1) : [];
            $canAccess = $item['canAccess'] ?? true;
            $isActive = $canAccess && (
                $currentRoute === $routeName
                || str_starts_with((string) $currentRoute, $routeName . '.')
            );
            $routeHref = $isArrayRoute
                ? route($routeName, ...$routeParams)
                : route($routeName);
        @endphp

        @if ($canAccess)
            <x-button.button
                :href="$routeHref"
                :variant="$isActive ? 'primary' : 'outlined'"
                label="{{ $item['label'] }}"
                size="none"
                style="{{ $itemStyle }}"
                @class([
                    $itemClass,
                    'hover:bg-primary hover:text-white' => ! $isActive,
                    'hover:!bg-primary hover:!text-white' => $isActive,
                ])
            />
        @else
            <div
                class="relative flex h-full"
                x-data="{ open: false }"
            >
                <span
                    class="flex h-full"
                    x-ref="navTrigger"
                    @mouseenter="open = true"
                    @mouseleave="open = false"
                >
                    <x-button.button
                        variant="outlined"
                        label="{{ $item['label'] }}"
                        disabled
                        size="none"
                        style="{{ $itemStyle }}"
                        @class([$itemClass, 'cursor-not-allowed opacity-50'])
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
