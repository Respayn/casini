@props(['params'])

@php
    $value = is_array($params) ? ($params['value'] ?? null) : null;
    $updatedAt = is_array($params) ? ($params['updatedAt'] ?? null) : null;
@endphp

<x-data.table-cell class="whitespace-nowrap" {{ $attributes }}>
    @if ($value === null)
        -
    @else
        @if ($updatedAt)
            <span class="text-secondary-text">{{ $updatedAt->format('H:i') }}, {{ $updatedAt->format('d.m.y') }}</span> /
        @endif
        {{ Number::currency($value, in: 'RUB', locale: 'ru') }}
    @endif
</x-data.table-cell>
