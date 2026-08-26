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

<x-data.table-cell class="bg-table-summary-bg !p-0 h-1" {{ $attributes }}>
    @if ($slots === [])
        <div class="flex h-full items-center justify-end px-2.5 py-2 font-bold">-</div>
    @else
        <div class="grid h-full auto-rows-fr divide-y divide-table-cell">
            @foreach ($slots as $slot)
                @php
                    $planPercent = (($slot['format'] ?? null) === 'percent' && is_numeric($slot['value'] ?? null))
                        ? (int) $slot['value']
                        : null;
                    $slotBg = $highlightUnmetKpi ? $kpiHighlightBg($planPercent) : null;
                @endphp
                <div
                    class="flex h-full items-center justify-end grow px-2.5 py-2 whitespace-nowrap gap-1 font-bold"
                    @if ($slotBg !== null) style="background-color: {{ $slotBg }}" @endif
                >
                    @if (isset($slot['value']) && $slot['value'] !== null && $slot['value'] !== '')
                        <span>{{ $formatValue($slot) }}</span>
                        @if (($slot['format'] ?? null) === 'percent')
                            <x-overlay.tooltip>
                                % считается по основным параметрам клиенто-проектов
                            </x-overlay.tooltip>
                        @endif
                    @else
                        -
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-data.table-cell>
