@props(['params'])

{{-- Высота строки на tr вложенной таблицы, иначе Firefox считает высоту по тексту. --}}
<x-data.table-cell {{ $attributes }} class="!p-0" style="vertical-align: top">
    <table class="w-full border-collapse">
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
            <tr style="height: 44px">
                <td
                    class="whitespace-nowrap {{ ! empty($param['highlight']) ? 'font-bold' : '' }}"
                    style="height: 44px; max-height: 44px; vertical-align: middle; padding: 0 10px; overflow: hidden; {{ $loop->first ? '' : 'border-top: 1px solid var(--color-table-cell);' }}"
                >
                    <span class="inline-flex items-center gap-1">
                        <span>{{ $parts['value'] }}</span>
                        @if ($parts['suffix'] !== null)
                            <span class="text-xs font-normal text-secondary-text">{{ $parts['suffix'] }}</span>
                        @endif
                    </span>
                </td>
            </tr>
        @endforeach
    </table>
</x-data.table-cell>
