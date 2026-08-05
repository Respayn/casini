@blaze

@props(['params'])

@php
    $formatValue = static function (array $slot): string {
        if (! array_key_exists('value', $slot) || $slot['value'] === null || $slot['value'] === '') {
            return '-';
        }

        return match ($slot['format'] ?? null) {
            'currency' => \Illuminate\Support\Number::currency($slot['value'], in: 'RUB', locale: 'ru', precision: 0),
            'percent' => $slot['value'].'%',
            default => (string) $slot['value'],
        };
    };

    $rows = [];
    // Минимум как подпись «План»/«Факт» в шапке — иначе пустые дни сжимаются до ширины «-».
    $maxSizer = 'План';

    foreach ($params as $parameter) {
        $planText = $formatValue($parameter['plan']);
        $factText = is_numeric($parameter['fact']['value'] ?? null)
            ? $formatValue($parameter['fact'])
            : '-';
        $sizerText = mb_strlen($factText) >= mb_strlen($planText) ? $factText : $planText;

        if (mb_strlen($sizerText) > mb_strlen($maxSizer)) {
            $maxSizer = $sizerText;
        }

        $rows[] = [
            'planText' => $planText,
            'factText' => $factText,
            'hasFact' => is_numeric($parameter['fact']['value'] ?? null),
        ];
    }
@endphp

{{--
  Ширина: скрытая строка-измеритель (visibility:collapse) в nested table.
  Высота: absolute divider inset-y-0 left-50% — линия на всю высоту внешней ячейки.
--}}
<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    <div
        class="relative h-full"
        style="width: max-content; min-width: 100%"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute"
            style="top: 0; bottom: 0; left: 50%; width: 1px; margin-left: -0.5px; background-color: var(--color-table-cell);"
        ></div>

        <table
            class="relative h-full w-full border-collapse"
            style="height: 100%; width: max-content; min-width: 100%"
        >
            <tr aria-hidden="true" style="visibility: collapse">
                <td class="whitespace-nowrap px-2.5 font-bold">{{ $maxSizer }}</td>
                <td class="whitespace-nowrap px-2.5 font-bold">{{ $maxSizer }}</td>
            </tr>

            @foreach ($rows as $row)
                <tr class="{{ $loop->last ? '' : 'border-b border-table-cell' }}">
                    <td
                        class="align-middle whitespace-nowrap px-2.5 py-2"
                        style="width: 50%; color: #A0B5D2"
                    >{{ $row['planText'] }}</td>
                    <td
                        class="align-middle whitespace-nowrap px-2.5 py-2 font-bold"
                        style="width: 50%"
                    >
                        @if ($row['hasFact'])
                            {{ $row['factText'] }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
</x-data.table-cell>
