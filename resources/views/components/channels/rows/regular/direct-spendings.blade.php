@props(['params'])

@php
    $value = is_array($params) ? ($params['value'] ?? null) : null;
@endphp

<x-data.table-cell class="whitespace-nowrap" {{ $attributes }}>
    {{ \App\Helpers\MoneyDisplay::currency($value) }}
</x-data.table-cell>
