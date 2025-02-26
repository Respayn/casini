<?php

use App\Services\ProductService;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;

new class extends Component {
    private ProductService $productService;

    public Collection $products;

    public int $selectedProductId = 0;
    public string $notificationText = '';

    public function boot(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function mount()
    {
        $this->products = $this->productService->getProducts();
    }

    public function updateProduct(int $id)
    {
        $product = $this->products->firstWhere('id', $id);
        $this->productService->updateProduct($product);
    }

    public function updateNotification()
    {
        $product = $this->products->firstWhere('id', $this->pull('selectedProductId'));
        $product->notification = trim($this->pull('notificationText'));
        $this->productService->updateProduct($product);
        $this->dispatch('modal-hide', name: 'product-dictionary-notification-edit-modal');
    }
}; ?>

<div>
    <x-data.table>
        <x-data.table-columns>
            <x-data.table-column>Продукт</x-data.table-column>
            <x-data.table-column>Закрыть доступ</x-data.table-column>
            <x-data.table-column>Пользовательские уведомления</x-data.table-column>
        </x-data.table-columns>

        <x-data.table-rows>
            @foreach ($products as $index => $product)
                <x-data.table-row wire:key="product-{{ $product->code }}">
                    <x-data.table-cell x-data="{ isEditing: false }">
                        <div class="flex items-center justify-between gap-2">
                            <div x-show="!isEditing">
                                {{ $product->name }}
                            </div>
                            <div x-show="isEditing">
                                <x-form.input-text
                                    x-ref="input"
                                    x-cloak
                                    wire:model="products.{{ $index }}.name"
                                />
                            </div>
                            <div>
                                <x-button.button
                                    icon="icons.edit"
                                    variant="ghost"
                                    x-show="!isEditing"
                                    x-on:click="isEditing = true; $nextTick(() => {$refs.input.focus()})"
                                />
                                <x-button.button
                                    variant="ghost"
                                    icon="icons.check"
                                    x-on:click="isEditing = false; $wire.updateProduct({{ $product->id }})"
                                    x-show="isEditing"
                                />
                                <x-button.button
                                    variant="ghost"
                                    icon="icons.close"
                                    x-on:click="isEditing = false; $wire.products[{{ $index }}].name = '{{ $product->name }}'"
                                    x-show="isEditing"
                                />
                            </div>
                        </div>
                    </x-data.table-cell>
                    <x-data.table-cell class="text-center">
                        <input
                            type="checkbox"
                            wire:model="products.{{ $index }}.isRestricted"
                            wire:click="updateProduct({{ $product->id }})"
                        />
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <div class="flex justify-center">
                            <x-overlay.modal-trigger name="product-dictionary-notification-edit-modal">
                                <x-button.button
                                    icon="icons.chat"
                                    :variant="$product->notification ? 'primary' : 'ghost'"
                                    x-on:click="$wire.selectedProductId = {{ $product->id }}; $wire.notificationText = `{{ $product->notification }}`"
                                >
                                </x-button.button>
                            </x-overlay.modal-trigger>
                        </div>
                    </x-data.table-cell>
                </x-data.table-row>
            @endforeach
            <x-data.table-row>
                <x-data.table-cell>
                    Все продукты
                </x-data.table-cell>
                <x-data.table-cell class="text-center">
                    <input type="checkbox" />
                </x-data.table-cell>
                <x-data.table-cell>
                    <div class="flex justify-center">
                        <x-icons.chat />
                    </div>
                </x-data.table-cell>
            </x-data.table-row>
        </x-data.table-rows>
    </x-data.table>
    <div class="w-3/4 text-sm font-normal italic text-gray-400">
        Добавление новых продуктов через программиста
    </div>

    <x-overlay.modal name="product-dictionary-notification-edit-modal">
        <x-slot:body>
            <x-form.form class="mb-7">
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                    >Содержание уведомления</x-form.form-label>
                    <div>
                        <x-form.textarea wire:model="notificationText"></x-form.input-text>
                    </div>
                </x-form.form-field>
            </x-form.form>
            <div class="flex justify-between">
                <x-button.button
                    variant="primary"
                    wire:click="updateNotification"
                >
                    <x-slot:label>
                        Сохранить
                    </x-slot>
                </x-button.button>
                <x-button.button
                    label="Отменить"
                    x-on:click="$dispatch('modal-hide', { name: 'product-dictionary-notification-edit-modal' })"
                ></x-button.button>
            </div>
        </x-slot>
    </x-overlay.modal>
</div>
