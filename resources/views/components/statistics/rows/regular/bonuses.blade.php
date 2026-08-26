@props(['params'])

@php
    $kind = is_array($params) ? ($params['kind'] ?? null) : null;
    $value = is_array($params) ? ($params['value'] ?? null) : (is_numeric($params) ? $params : null);
@endphp

<x-data.table-cell {{ $attributes }}>
    @if ($kind === 'not_configured')
        Не настроены
    @elseif ($kind === 'fill_check')
        <span class="italic">Заполните Чек клиента</span>
    @elseif ($kind === 'amount' && is_numeric($value))
        {{ Number::currency($value, in: 'RUB', locale: 'ru', precision: 0) }}
    @elseif (is_numeric($params))
        {{ Number::currency($params, in: 'RUB', locale: 'ru', precision: 0) }}
    @else
        -
    @endif
</x-data.table-cell>
