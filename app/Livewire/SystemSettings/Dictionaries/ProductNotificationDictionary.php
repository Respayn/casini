<?php

namespace App\Livewire\SystemSettings\Dictionaries;

use App\Models\Product;
use App\Services\ProductNotificationService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProductNotificationDictionary extends Component
{
    private ProductNotificationService $productNotificationService;

    public Collection $productNotifications;

    public function boot(ProductNotificationService $productNotificationService)
    {
        $this->productNotificationService = $productNotificationService;
    }

    public function mount()
    {
        $this->productNotifications = $this->productNotificationService->getProductNotifications();
    }

    public function updateProductNotification(int $id)
    {
        $productNotification = $this->productNotifications->firstWhere('id', $id);
        $this->productNotificationService->updateProductNotification($productNotification);
    }

    public function render()
    {
        return view('livewire.system-settings.dictionaries.product-notification-dictionary');
    }
}
