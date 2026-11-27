@props([
    'params',
    'highlightUnmetKpi' => false,
    'treatMissingPlanAsUnmet' => false,
])

@php
    use App\Helpers\PlanValueHelper;

    $formatValue = static function (array $slot): string {
        if (! array_key_exists('value', $slot) || $slot['value'] === null || $slot['value'] === '') {
            return '-';
        }

        return match ($slot['format'] ?? null) {
            'currency' => \App\Helpers\MoneyDisplay::currency($slot['value']),
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
        $planText = PlanValueHelper::format(
            $parameter['plan']['value'] ?? null,
            $parameter['plan']['format'] ?? null,
        );
        $factText = is_numeric($parameter['fact']['value'] ?? null)
            ? $formatValue($parameter['fact'])
            : '-';

        $planPercent = null;
        $planValue = $parameter['plan']['value'] ?? null;
        $factValue = $parameter['fact']['value'] ?? null;
        if (is_numeric($factValue) && is_numeric($planValue) && (float) $planValue != 0.0) {
            $planPercent = (int) round(((float) $factValue / (float) $planValue) * 100);
        } elseif (is_numeric($factValue) && $treatMissingPlanAsUnmet) {
            $planPercent = 0;
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
  Высота на tr вложенной таблицы (44px). У div внутри ячейки Firefox высоту не держит:
  прочерк ниже, сумма с процентом выше, линии между днями не сходятся.
  Ширина: table-layout: fixed + одинаковый измеритель в обеих половинах,
  чтобы вертикаль «План | Факт» совпадала с шапкой (там тоже 50/50).
--}}
<x-data.table-cell {{ $attributes }} class="!p-0" style="vertical-align: top">
    <table class="border-collapse" style="table-layout: fixed; width: max-content; min-width: 100%">
        <tr aria-hidden="true" style="height: 0; line-height: 0">
            <td style="width: 50%; height: 0; max-height: 0; padding: 0 10px; border: 0; line-height: 0; overflow: hidden">
                <div class="pointer-events-none whitespace-nowrap font-bold" style="height: 0; overflow: hidden">{{ $maxSizer }}</div>
            </td>
            <td style="width: 50%; height: 0; max-height: 0; padding: 0 10px; border: 0; line-height: 0; overflow: hidden">
                <div class="pointer-events-none whitespace-nowrap font-bold" style="height: 0; overflow: hidden">{{ $maxSizer }}</div>
            </td>
        </tr>
        @foreach ($rows as $row)
            <tr style="height: 44px">
                <td
                    class="whitespace-nowrap"
                    style="width: 50%; height: 44px; max-height: 44px; vertical-align: middle; padding: 0 10px; overflow: hidden; color: #A0B5D2; {{ $loop->first ? '' : 'border-top: 1px solid var(--color-table-cell);' }}"
                >{{ $row['planText'] }}</td>
                <td
                    class="whitespace-nowrap font-bold"
                    style="width: 50%; height: 44px; max-height: 44px; vertical-align: middle; padding: 0 10px; overflow: hidden; border-left: 1px solid var(--color-table-cell); {{ $loop->first ? '' : 'border-top: 1px solid var(--color-table-cell);' }}{{ $row['factBg'] !== null ? 'background-color: '.$row['factBg'].';' : '' }}"
                >
                    @if ($row['hasFact'])
                        <span class="inline-flex items-center gap-1">
                            <span>{{ $row['factText'] }}</span>
                            @if ($row['planPercent'] !== null)
                                <span class="text-xs font-normal text-secondary-text">({{ $row['planPercent'] }}%)</span>
                            @endif
                        </span>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</x-data.table-cell>
