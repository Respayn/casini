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
            'percent' => $slot['value'].'%',
            default => (string) $slot['value'],
        };
    };

    $kpiHighlightBg = static function (?int $planPercent): ?string {
        if ($planPercent === null) {
            return null;
        }

        return $planPercent >= 90 ? '#EBFCF0' : '#FCEBEB';
    };

    $slots = is_array($params) ? $params : [];
@endphp

<x-data.table-cell class="bg-table-summary-bg !p-0" style="vertical-align: top" {{ $attributes }}>
    <table class="w-full border-collapse">
        @forelse ($slots as $slot)
            @php
                $planPercent = (($slot['format'] ?? null) === 'percent' && is_numeric($slot['value'] ?? null))
                    ? (int) $slot['value']
                    : null;
                $slotBg = $highlightUnmetKpi ? $kpiHighlightBg($planPercent) : null;
            @endphp
            <tr style="height: 44px">
                <td
                    class="whitespace-nowrap text-right font-bold"
                    style="height: 44px; max-height: 44px; vertical-align: middle; padding: 0 10px; overflow: hidden; {{ $loop->first ? '' : 'border-top: 1px solid var(--color-table-cell);' }}{{ $slotBg !== null ? 'background-color: '.$slotBg.';' : '' }}"
                >
                    @if (isset($slot['value']) && $slot['value'] !== null && $slot['value'] !== '')
                        <span class="inline-flex items-center justify-end gap-1">
                            <span>{{ $formatValue($slot) }}</span>
                            @if (($slot['format'] ?? null) === 'percent')
                                <x-overlay.tooltip>
                                    % считается по основным параметрам клиенто-проектов
                                </x-overlay.tooltip>
                            @endif
                        </span>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr style="height: 44px">
                <td class="whitespace-nowrap text-right font-bold" style="height: 44px; vertical-align: middle; padding: 0 10px">-</td>
            </tr>
        @endforelse
    </table>
</x-data.table-cell>
