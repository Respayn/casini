@props(['params'])

{{-- Установка высоты ячейки в маленькое значение - хак
https://stackoverflow.com/questions/3542090/how-to-make-div-fill-td-height--}}
<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    <div class="flex flex-col justify-evenly h-full">
        @foreach ($params as $param)
            <div class="flex items-center grow px-2.5 py-2">
                @if (is_numeric($param['value']))
                    <span>
                        @switch($param['format'])
                            @case('currency')
                                {{ Number::currency($param['value'], in: 'RUB', locale: 'ru', precision: 0) }}
                                @break

                            @case('percent')
                                {{ $param['value'] }}%
                                @break

                            @default
                                {{ $param['value'] }}
                        @endswitch
                    </span>
                @else
                    <span>-</span>
                @endif
            </div>

            @if (!$loop->last)
                <hr style="color: #d0ddee">
            @endif
        @endforeach
    </div>
</x-data.table-cell>