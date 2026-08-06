@props(['params'])

@php
    $value = is_array($params) ? ($params['value'] ?? null) : null;
@endphp

<x-data.table-cell class="whitespace-nowrap" {{ $attributes }}>
    {{ $value === null ? '-' : Number::currency($value, in: 'RUB', locale: 'ru') }}
</x-data.table-cell>
