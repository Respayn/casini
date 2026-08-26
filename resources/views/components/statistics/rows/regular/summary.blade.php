@props(['params'])

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

    $slots = is_array($params) ? $params : [];
@endphp

<x-data.table-cell {{ $attributes }} class="!p-0 h-1">
    @if ($slots === [])
        <div class="flex h-full items-center px-2.5 py-2">-</div>
    @else
        <div class="grid h-full auto-rows-fr divide-y divide-table-cell">
            @foreach ($slots as $slot)
                <div class="flex items-center grow px-2.5 py-2 whitespace-nowrap gap-1">
                    @if (isset($slot['value']) && $slot['value'] !== null && $slot['value'] !== '')
                        <span>{{ $formatValue($slot) }}</span>
                        @if (isset($slot['plan_percent']) && is_numeric($slot['plan_percent']))
                            <span class="text-secondary-text">({{ $slot['plan_percent'] }}%)</span>
                        @endif
                    @else
                        -
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-data.table-cell>
