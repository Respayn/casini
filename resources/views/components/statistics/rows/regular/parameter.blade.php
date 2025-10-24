@props(['params'])

{{-- Установка высоты ячейки в маленькое значение - хак
https://stackoverflow.com/questions/3542090/how-to-make-div-fill-td-height--}}
<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    <div class="flex flex-col justify-evenly h-full">
        @foreach ($params as $param)
            <div class="flex grow items-center whitespace-nowrap justify-between ps-2.5 pe-0.5 py-2 gap-5">
                <span @if ($param['highlight'])class="font-bold" @endif>{{ $param['name'] }}</span>
                @if ($param['highlight'])
                    <x-icons.parameter-primary />
                @else
                    <x-icons.parameter-secondary />
                @endif
            </div>
            @if (!$loop->last)
                <hr style="color: #d0ddee">
            @endif
        @endforeach
    </div>
</x-data.table-cell>