@props(['params'])

{{-- Установка высоты ячейки в маленькое значение - хак
https://stackoverflow.com/questions/3542090/how-to-make-div-fill-td-height--}}
<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    <div class="grid auto-rows-fr h-full divide-y divide-table-cell">
        @foreach (($params ?? []) as $param)
            <div class="flex items-center grow px-2.5">
                <span>
                    @if (isset($param['value']))
                        {{ \App\Helpers\PlanValueHelper::format($param['value'], $param['format'] ?? null) }}
                    @else
                        -
                    @endif
                </span>
            </div>
        @endforeach
    </div>
</x-data.table-cell>
