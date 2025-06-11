@props([
    'previous' => null,
    'after' => null,
    'items' => [],
])

@php
    $currentRoute = Route::currentRouteName();
@endphp

<div class="flex gap-2.5" {{ $attributes }}>
    {{ $previous }}

    @foreach ($items as $item)
        @php
            $routeValue = $item['route'];
            $isArrayRoute = is_array($routeValue);
            $routeName = $isArrayRoute ? $routeValue[0] : $routeValue;
            $routeParams = $isArrayRoute ? array_slice($routeValue, 1) : [];
            $isActive = $currentRoute === $routeName;
            $routeHref = $isActive ? '' : ($isArrayRoute ? route($routeName, ...$routeParams) : route($routeName));
        @endphp

        <x-button.button
            :href="$routeHref"
            :variant="$isActive ? 'primary' : 'outlined'"
            label="{{ $item['label'] }}"
            @class([
                'hover:bg-primary hover:text-white' => !$isActive,
                'hover:!bg-primary hover:!text-white' => $isActive,
            ])
        />
    @endforeach

    {{ $after }}
</div>
