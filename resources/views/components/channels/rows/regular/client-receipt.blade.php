@props(['params'])

@if ($params === null)
    <x-data.table-cell {{ $attributes }}>
        -
    </x-data.table-cell>
@else
    <x-data.table-cell {{ $attributes }}>
        {{ $params ? Number::currency($params, in: 'RUB', locale: 'ru') : '-' }}
    </x-data.table-cell>
@endif
