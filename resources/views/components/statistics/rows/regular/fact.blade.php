@blaze

@props(['params'])

{{-- Установка высоты ячейки в маленькое значение - хак
https://stackoverflow.com/questions/3542090/how-to-make-div-fill-td-height--}}
<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    <div class="grid auto-rows-auto h-full divide-y divide-table-cell">
        @foreach ($params as $parameter)
            @php
                $plan = $parameter['plan'];
                $fact = $parameter['fact'];
            @endphp
            
            <div class="grid grid-cols-2 divide-x divide-table-cell">
                <div class="flex items-center ps-2.5">
                    <span style="color: #A0B5D2">
                        @switch($plan['format'])
                            @case('currency')
                                {{ Number::currency($plan['value'], in: 'RUB', locale: 'ru', precision: 0) }}
                                @break

                            @case('percent')
                                {{ $plan['value'] }}%
                                @break

                            @default
                                {{ $plan['value'] }}
                        @endswitch
                    </span>
                </div>
                <div class="flex items-center ps-2.5">
                    @if (is_numeric($fact['value']))
                        <span class="font-bold">
                            @switch($fact['format'])
                                @case('currency')
                                    {{ Number::currency($fact['value'], in: 'RUB', locale: 'ru', precision: 0) }}
                                    @break

                                @case('percent')
                                    {{ $fact['value'] }}%
                                    @break

                                @default
                                    {{ $fact['value'] }}
                            @endswitch
                        </span>
                    @else
                        <span>-</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-data.table-cell>