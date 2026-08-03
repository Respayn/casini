@props(['params'])

@php
    $value = is_array($params) ? ($params['value'] ?? null) : null;
    $projectId = is_array($params) ? ($params['projectId'] ?? null) : null;
    $canRefresh = is_array($params) && ($params['canRefresh'] ?? false) && $projectId;
@endphp

@if ($canRefresh)
    <x-data.table-cell
        class="cursor-pointer whitespace-nowrap hover:bg-blue-50"
        title="Обновить расход в Директе"
        wire:click="refreshDirectSpendings({{ (int) $projectId }})"
        wire:loading.class="opacity-50"
        wire:target="refreshDirectSpendings({{ (int) $projectId }})"
    >
        {{ $value === null ? '-' : Number::currency($value, in: 'RUB', locale: 'ru') }}
    </x-data.table-cell>
@else
    <x-data.table-cell class="whitespace-nowrap" {{ $attributes }}>
        {{ $value === null ? '-' : Number::currency($value, in: 'RUB', locale: 'ru') }}
    </x-data.table-cell>
@endif
