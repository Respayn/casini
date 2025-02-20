<div>
    <x-data.table>
        <x-data.table-columns>
            <x-data.table-column>Продукт</x-data.table-column>
            <x-data.table-column>Категория</x-data.table-column>
            <x-data.table-column>Системные уведомления</x-data.table-column>
        </x-data.table-columns>

        <x-data.table-rows>
            @foreach ($productNotifications as $index => $productNotification)
                <x-data.table-row wire:key="product-notification-{{ $productNotification->code }}">
                    <x-data.table-cell>
                        {{ $productNotification->product->name ?? 'Все продукты' }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <x-form.select
                            :options="[
                                [
                                    'label' => App\Enums\ProductNotificationCategory::OTHER->label(),
                                    'value' => App\Enums\ProductNotificationCategory::OTHER,
                                ],
                                [
                                    'label' => App\Enums\ProductNotificationCategory::IMPORTANT->label(),
                                    'value' => App\Enums\ProductNotificationCategory::IMPORTANT,
                                ],
                            ]"
                            wire:model="productNotifications.{{ $index }}.category"
                            x-on:change="$wire.updateProductNotification({{ $productNotification->id }})"
                        />
                    </x-data.table-cell>
                    <x-data.table-cell>
                        {{ $productNotification->content }}
                    </x-data.table-cell>
                </x-data.table-row>
            @endforeach
        </x-data.table-rows>
    </x-data.table>
    <div class="w-3/4 text-sm font-normal italic text-gray-400">
        Добавление или редактирование содержаний уведомлений через программиста
    </div>
</div>
