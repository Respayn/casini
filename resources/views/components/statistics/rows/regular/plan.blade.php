@props(['params'])

{{-- Установка высоты ячейки в маленькое значение - хак
https://stackoverflow.com/questions/3542090/how-to-make-div-fill-td-height--}}
<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    <div class="grid auto-rows-fr h-full divide-y divide-table-cell">
        @foreach (($params ?? []) as $param)
            @php
                $parts = isset($param['value'])
                    ? \App\Helpers\PlanValueHelper::planColumnParts(
                        $param['value'],
                        $param['format'] ?? null,
                        $param['code'] ?? null,
                        ! empty($param['highlight']),
                    )
                    : ['value' => '-', 'suffix' => null];
            @endphp
            <div class="flex items-center grow gap-1 px-2.5 whitespace-nowrap {{ ! empty($param['highlight']) ? 'font-bold' : '' }}">
                <span>{{ $parts['value'] }}</span>
                @if ($parts['suffix'] !== null)
                    <span class="text-xs font-normal text-secondary-text">{{ $parts['suffix'] }}</span>
                @endif
            </div>
        @endforeach
    </div>
</x-data.table-cell>
