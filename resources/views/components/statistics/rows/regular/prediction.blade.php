@props(['params'])

@php
    $formatForecast = static function (array $slot): string {
        if (($slot['kind'] ?? null) === 'insufficient') {
            return 'мало данных';
        }

        if (($slot['kind'] ?? null) !== 'forecast' || ! isset($slot['value']) || $slot['value'] === null || $slot['value'] === '') {
            return '-';
        }

        $value = match ($slot['format'] ?? null) {
            'currency' => \Illuminate\Support\Number::currency(
                $slot['value'],
                in: 'RUB',
                locale: 'ru',
                precision: abs((float) $slot['value'] - round((float) $slot['value'])) < 0.001 ? 0 : 2,
            ),
            'percent' => $slot['value'].'%',
            default => (string) $slot['value'],
        };

        return '~'.$value;
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
                    $text = $formatForecast($slot);
                    $isInsufficient = ($slot['kind'] ?? null) === 'insufficient';
                @endphp
                <div class="flex items-center grow px-2.5 py-2 whitespace-nowrap {{ $isInsufficient ? 'italic' : '' }}">
                    {{ $text }}
                </div>
            @endforeach
        </div>
    @endif
</x-data.table-cell>
