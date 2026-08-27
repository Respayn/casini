@props([
    'rows' => 8,
])

{{-- Скелетон таблицы отчёта (Каналы / Статистика): для оверлея поверх текущей таблицы --}}
<div
    {{ $attributes->merge([
        'role' => 'status',
        'aria-label' => 'Загрузка таблицы',
        'style' => 'display: flex; height: 100%; min-height: 240px; flex-direction: column; overflow: hidden; border-radius: 0.125rem; border: 1px solid #d0ddee; background: #fff; '.$attributes->get('style'),
    ]) }}
>
    <div style="display: flex; flex-shrink: 0; gap: 0.75rem; padding: 0.5rem 0.625rem; background-color: #212a35">
        @foreach (range(1, 8) as $col)
            <div
                class="animate-pulse"
                style="height: 12px; flex: 1 1 0; min-width: 0; border-radius: 0.25rem; background-color: rgba(255,255,255,0.25)"
            ></div>
        @endforeach
    </div>

    <div style="display: flex; min-height: 0; flex: 1; flex-direction: column">
        @foreach (range(1, (int) $rows) as $row)
            <div
                style="display: flex; flex: 1; align-items: center; gap: 0.75rem; min-height: 48px; padding: 0 0.625rem; border-top: 1px solid #d0ddee; background-color: {{ $row % 2 === 1 ? '#F9F9F9' : '#FFFFFF' }}"
            >
                @foreach (range(1, 8) as $col)
                    <div
                        class="animate-pulse"
                        style="height: 14px; flex: 1 1 0; min-width: 0; border-radius: 0.25rem; background-color: #e5e7eb"
                    ></div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
