@blaze

@props(['params'])

@php
    $parameters = [
        ['plan' => 1871, 'fact' => '84%'],
        ['plan' => 8, 'fact' => '']
    ]
@endphp

{{-- Установка высоты ячейки в маленькое значение - хак
https://stackoverflow.com/questions/3542090/how-to-make-div-fill-td-height--}}
<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    <div class="grid grid-flow-col auto-rows-auto h-full divide-y divide-table-cell">
        @foreach ($parameters as $parameter)
            <div class="grid grid-cols-2 divide-x divide-table-cell">
                <div class="flex items-center ps-2.5">{{ $parameter['plan'] }}</div>
                <div class="flex items-center ps-2.5">{{ $parameter['fact'] }}</div>
            </div>
        @endforeach
    </div>
</x-data.table-cell>