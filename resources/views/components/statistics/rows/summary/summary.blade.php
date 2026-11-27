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

    // В строках «Итого» цвет KPI не подсвечиваем - только обычный фон сводки.
    $slots = is_array($params) ? $params : [];
    $useFullHeight = count($slots) <= 1;
@endphp

@if ($useFullHeight)
    @php
        $slot = $slots[0] ?? null;
    @endphp
    <x-data.table-cell
        {{ $attributes->class(['bg-table-summary-bg', '!p-0']) }}
        style="vertical-align: middle"
    >
        <div class="flex min-h-[44px] items-center justify-end px-2.5 py-3.5 font-bold whitespace-nowrap">
            @if ($slot !== null && isset($slot['value']) && $slot['value'] !== null && $slot['value'] !== '')
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
        </div>
    </x-data.table-cell>
@else
    <x-data.table-cell class="bg-table-summary-bg !p-0" style="vertical-align: top" {{ $attributes }}>
        <table class="w-full border-collapse">
            @foreach ($slots as $slot)
                <tr style="height: 44px">
                    <td
                        class="whitespace-nowrap text-right font-bold"
                        style="height: 44px; max-height: 44px; vertical-align: middle; padding: 0 10px; overflow: hidden; {{ $loop->first ? '' : 'border-top: 1px solid var(--color-table-cell);' }}"
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
            @endforeach
        </table>
    </x-data.table-cell>
@endif
