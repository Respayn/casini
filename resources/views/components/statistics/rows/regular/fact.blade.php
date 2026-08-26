@blaze

@props([
    'params',
    'highlightUnmetKpi' => false,
])

@php
    $formatValue = static function (array $slot): string {
        if (! array_key_exists('value', $slot) || $slot['value'] === null || $slot['value'] === '') {
            return '-';
        }

        return match ($slot['format'] ?? null) {
            'currency' => \Illuminate\Support\Number::currency(
                $slot['value'],
                in: 'RUB',
                locale: 'ru',
                precision: abs((float) $slot['value'] - round((float) $slot['value'])) < 0.001 ? 0 : 2,
            ),
            'percent' => $slot['value'].'%',
            default => (string) $slot['value'],
        };
    };

    $kpiHighlightBg = static function (?int $planPercent): ?string {
        if ($planPercent === null) {
            return null;
        }

        if ($planPercent >= 90) {
            return '#EBFCF0';
        }

        return '#FCEBEB';
    };

    $rows = [];
    // Минимум как подпись «План»/«Факт» в шапке — иначе пустые дни сжимаются до ширины «-».
    $maxSizer = 'План';

    foreach ($params as $parameter) {
        $planText = $formatValue($parameter['plan']);
        $factText = is_numeric($parameter['fact']['value'] ?? null)
            ? $formatValue($parameter['fact'])
            : '-';

        $planPercent = null;
        $planValue = $parameter['plan']['value'] ?? null;
        $factValue = $parameter['fact']['value'] ?? null;
        if (is_numeric($factValue) && is_numeric($planValue) && (float) $planValue != 0.0) {
            $planPercent = (int) round(((float) $factValue / (float) $planValue) * 100);
        }

        $factWithPercent = $planPercent !== null
            ? $factText.' ('.$planPercent.'%)'
            : $factText;
        $sizerText = mb_strlen($factWithPercent) >= mb_strlen($planText) ? $factWithPercent : $planText;

        if (mb_strlen($sizerText) > mb_strlen($maxSizer)) {
            $maxSizer = $sizerText;
        }

        $rows[] = [
            'planText' => $planText,
            'factText' => $factText,
            'planPercent' => $planPercent,
            'hasFact' => is_numeric($parameter['fact']['value'] ?? null),
            'factBg' => $highlightUnmetKpi ? $kpiHighlightBg($planPercent) : null,
        ];
    }
@endphp

{{--
  Высота: CSS grid auto-rows-fr — как у «Параметр»/«План», иначе nested table
  раздаёт лишнюю высоту неравномерно и строка «Рекламный бюджет» уезжает вверх.
  Ширина: скрытый измеритель (как раньше visibility:collapse в table).
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

        <div
            aria-hidden="true"
            class="pointer-events-none overflow-hidden"
            style="height: 0; visibility: hidden"
        >
            <div class="grid grid-cols-2">
                <div class="whitespace-nowrap px-2.5 font-bold">{{ $maxSizer }}</div>
                <div class="whitespace-nowrap px-2.5 font-bold">{{ $maxSizer }}</div>
            </div>
        </div>

        <div class="grid h-full auto-rows-fr divide-y divide-table-cell">
            @foreach ($rows as $row)
                <div class="grid h-full grid-cols-2">
                    <div
                        class="flex items-center whitespace-nowrap px-2.5 py-2"
                        style="color: #A0B5D2"
                    >{{ $row['planText'] }}</div>
                    <div
                        class="flex items-center whitespace-nowrap px-2.5 py-2 font-bold gap-1"
                        @if ($row['factBg'] !== null) style="background-color: {{ $row['factBg'] }}" @endif
                    >
                        @if ($row['hasFact'])
                            <span>{{ $row['factText'] }}</span>
                            @if ($row['planPercent'] !== null)
                                <span class="text-xs font-normal text-secondary-text">({{ $row['planPercent'] }}%)</span>
                            @endif
                        @else
                            -
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-data.table-cell>
