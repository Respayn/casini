@props(['params'])

@if ($params === null)
    <x-data.table-cell {{ $attributes }}>
        -
    </x-data.table-cell>
@else
    <x-data.table-cell {{ $attributes }}>
        {{ $params ? \App\Helpers\MoneyDisplay::currency($params) : '-' }}
    </x-data.table-cell>
@endif
