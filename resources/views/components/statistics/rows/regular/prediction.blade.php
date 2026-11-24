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

<x-data.table-cell {{ $attributes }} class="!p-0" style="vertical-align: top">
    <table class="w-full border-collapse">
        @forelse ($slots as $slot)
            @php
                $text = $formatForecast($slot);
                $isInsufficient = ($slot['kind'] ?? null) === 'insufficient';
            @endphp
            <tr style="height: 44px">
                <td
                    class="whitespace-nowrap {{ $isInsufficient ? 'italic' : '' }}"
                    style="height: 44px; max-height: 44px; vertical-align: middle; padding: 0 10px; overflow: hidden; {{ $loop->first ? '' : 'border-top: 1px solid var(--color-table-cell);' }}"
                >{{ $text }}</td>
            </tr>
        @empty
            <tr style="height: 44px">
                <td class="whitespace-nowrap" style="height: 44px; vertical-align: middle; padding: 0 10px">-</td>
            </tr>
        @endforelse
    </table>
</x-data.table-cell>
