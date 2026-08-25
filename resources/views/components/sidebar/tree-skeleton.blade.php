@props([
    'rows' => 4,
])

{{-- Скелетон дерева сайдбара: оверлей поверх списка сотрудник → клиент --}}
<div
    {{ $attributes->merge([
        'role' => 'status',
        'aria-label' => 'Обновление портфеля клиенто-проектов',
        'class' => 'flex h-full min-h-[200px] flex-col gap-2 pe-[15px]',
    ]) }}
>
    @foreach (range(1, (int) $rows) as $row)
        <div class="flex flex-col gap-1 pb-2">
            <div
                class="bg-secondary flex min-h-[42px] items-center justify-between rounded-[5px] p-[10px]"
            >
                <div class="flex items-center gap-[10px]">
                    <div
                        class="animate-pulse rounded-[4px]"
                        style="height: 18px; width: 18px; background-color: #d0ddee"
                    ></div>
                    <div
                        class="animate-pulse rounded-[4px]"
                        style="height: 14px; width: {{ 120 + ($row * 18) }}px; background-color: #d0ddee"
                    ></div>
                </div>
                <div
                    class="animate-pulse rounded-[4px]"
                    style="height: 12px; width: 12px; background-color: #d0ddee"
                ></div>
            </div>

            @if ($row <= 2)
                <div class="flex flex-col gap-1 ps-4">
                    <div
                        class="bg-secondary flex min-h-[42px] items-center justify-between rounded-[5px] p-[10px]"
                    >
                        <div
                            class="animate-pulse rounded-[4px]"
                            style="height: 12px; width: {{ 90 + ($row * 24) }}px; background-color: #e5e7eb"
                        ></div>
                        <div
                            class="animate-pulse rounded-[4px]"
                            style="height: 12px; width: 12px; background-color: #e5e7eb"
                        ></div>
                    </div>
                    <div
                        class="border-flat-border flex min-h-[42px] items-center gap-1 rounded-[5px] border p-[10px] ps-4"
                    >
                        <div
                            class="animate-pulse rounded-[4px]"
                            style="height: 12px; width: {{ 100 + ($row * 20) }}px; background-color: #e5e7eb"
                        ></div>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>
