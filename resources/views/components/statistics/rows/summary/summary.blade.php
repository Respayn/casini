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

<x-data.table-cell class="bg-table-summary-bg !p-0 h-1" {{ $attributes }}>
    @if ($slots === [])
        <div class="flex h-full items-center px-2.5 py-2 font-bold">-</div>
    @else
        <div class="grid h-full auto-rows-fr divide-y divide-table-cell">
            @foreach ($slots as $slot)
                <div class="flex items-center grow px-2.5 py-2 whitespace-nowrap gap-1 font-bold">
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
