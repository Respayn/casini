@props(['params'])

{{-- Высота строки на tr вложенной таблицы (44px). У div внутри ячейки Firefox
     высоту не держит, и линии между днями разъезжаются. --}}
<x-data.table-cell {{ $attributes }} class="!p-0" style="vertical-align: top">
    <table class="w-full border-collapse">
        @foreach ($params as $param)
            <tr style="height: 44px">
                <td
                    class="whitespace-nowrap"
                    style="height: 44px; max-height: 44px; vertical-align: middle; padding: 0 4px 0 10px; overflow: hidden; {{ $loop->first ? '' : 'border-top: 1px solid var(--color-table-cell);' }}"
                >
                    <div class="flex items-center justify-between gap-5">
                        <span @if ($param['highlight']) class="font-bold" @endif>{{ $param['name'] }}</span>
                        @if ($param['highlight'])
                            <x-icons.parameter-primary />
                        @else
                            <x-icons.parameter-secondary />
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </table>
</x-data.table-cell>
