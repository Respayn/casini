@props(['params'])

@php
    $value = is_array($params) ? ($params['value'] ?? null) : null;
    $updatedAt = is_array($params) ? ($params['updatedAt'] ?? null) : null;
    $projectId = is_array($params) ? ($params['projectId'] ?? null) : null;
    $canRefresh = is_array($params) && ($params['canRefresh'] ?? false) && $projectId;
@endphp

@if ($canRefresh)
    <x-data.table-cell
        class="cursor-pointer whitespace-nowrap hover:bg-blue-50"
        title="Обновить остаток бюджета"
        wire:click="refreshDirectBudget({{ (int) $projectId }})"
        wire:loading.class="opacity-50"
        wire:target="refreshDirectBudget({{ (int) $projectId }})"
    >
        @if ($value === null)
            -
        @else
            @if ($updatedAt)
                <span class="text-secondary-text">{{ $updatedAt->format('H:i') }}, {{ $updatedAt->format('d.m.Y') }}</span> /
            @endif
            {{ Number::currency($value, in: 'RUB', locale: 'ru') }}
        @endif
    </x-data.table-cell>
@else
    <x-data.table-cell class="whitespace-nowrap" {{ $attributes }}>
        @if ($value === null)
            -
        @else
            @if ($updatedAt)
                <span class="text-secondary-text">{{ $updatedAt->format('H:i') }}, {{ $updatedAt->format('d.m.Y') }}</span> /
            @endif
            {{ Number::currency($value, in: 'RUB', locale: 'ru') }}
        @endif
    </x-data.table-cell>
@endif
