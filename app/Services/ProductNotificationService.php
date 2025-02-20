<?php

namespace App\Services;

use App\Data\ProductNotificationData;
use App\Repositories\ProductNotificationRepository;
use Illuminate\Support\Arr;

class ProductNotificationService
{
    private ProductNotificationRepository $repository;

    public function __construct(ProductNotificationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getProductNotifications()
    {
        return collect($this->repository->all(with: ['product']));
    }

    public function updateProductNotification(ProductNotificationData $productNotification)
    {
        $this->repository->save(Arr::snake($productNotification->toArray()));
    }
}
