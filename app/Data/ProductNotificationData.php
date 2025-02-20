<?php

namespace App\Data;

use App\Enums\ProductNotificationCategory;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class ProductNotificationData extends Data implements Wireable
{
    use WireableData;

    public int $id;
    public ?int $productId;
    public ?ProductData $product;
    public ProductNotificationCategory $category;
    public string $content;
    public string $code;
}
