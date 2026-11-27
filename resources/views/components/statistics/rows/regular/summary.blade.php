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

    $slots = is_array($params) ? $params : [];
@endphp

<x-data.table-cell {{ $attributes }} class="!p-0" style="vertical-align: top">
    <table class="w-full border-collapse">
        @forelse ($slots as $slot)
            @php
                $isPrimary = ! empty($slot['highlight']);
                $planPercent = isset($slot['plan_percent']) && is_numeric($slot['plan_percent'])
                    ? (int) $slot['plan_percent']
                    : null;
                $slotBg = $highlightUnmetKpi ? $kpiHighlightBg($planPercent) : null;
            @endphp
            <tr style="height: 44px">
                <td
                    class="whitespace-nowrap {{ $isPrimary ? 'font-bold' : '' }}"
                    style="height: 44px; max-height: 44px; vertical-align: middle; padding: 0 10px; overflow: hidden; {{ $loop->first ? '' : 'border-top: 1px solid var(--color-table-cell);' }}{{ $slotBg !== null ? 'background-color: '.$slotBg.';' : '' }}"
                >
                    @if (isset($slot['value']) && $slot['value'] !== null && $slot['value'] !== '')
                        <span class="inline-flex items-center gap-1">
                            <span>{{ $formatValue($slot) }}</span>
                            @if ($planPercent !== null)
                                <span class="text-xs font-normal text-secondary-text">({{ $planPercent }}%)</span>
                            @endif
                        </span>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr style="height: 44px">
                <td class="whitespace-nowrap" style="height: 44px; vertical-align: middle; padding: 0 10px">-</td>
            </tr>
        @endforelse
    </table>
</x-data.table-cell>
