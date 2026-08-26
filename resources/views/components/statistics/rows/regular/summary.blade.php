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

    $slots = is_array($params) ? $params : [];
@endphp

<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    @if ($slots === [])
        <div class="flex h-full items-center px-2.5 py-2">-</div>
    @else
        <div class="grid h-full auto-rows-fr divide-y divide-table-cell">
            @foreach ($slots as $slot)
                @php
                    $isPrimary = ! empty($slot['highlight']);
                    $planPercent = isset($slot['plan_percent']) && is_numeric($slot['plan_percent'])
                        ? (int) $slot['plan_percent']
                        : null;
                    $slotBg = $highlightUnmetKpi ? $kpiHighlightBg($planPercent) : null;
                @endphp
                <div
                    class="flex items-center grow px-2.5 py-2 whitespace-nowrap gap-1 {{ $isPrimary ? 'font-bold' : '' }}"
                    @if ($slotBg !== null) style="background-color: {{ $slotBg }}" @endif
                >
                    @if (isset($slot['value']) && $slot['value'] !== null && $slot['value'] !== '')
                        <span>{{ $formatValue($slot) }}</span>
                        @if ($planPercent !== null)
                            <span class="text-xs font-normal text-secondary-text">({{ $planPercent }}%)</span>
                        @endif
                    @else
                        -
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-data.table-cell>
