@props(['params'])

<x-data.table-cell {{ $attributes }}>
    @switch ($params)
        @case ('active')
            <x-badge color="green" icon="play" >Активный</x-badge>
        @break

        @case ('inactive')
            <x-badge color="red" icon="pause">Неактивный</x-badge>
        @break

        @default
            -
    @endswitch
</x-data.table-cell>
