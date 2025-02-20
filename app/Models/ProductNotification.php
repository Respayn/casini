<?php

namespace App\Models;

use App\Enums\ProductNotificationCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductNotification extends Model
{
    protected $fillable = [
        'product_id',
        'category',
        'content',
        'code'
    ];

    protected function casts(): array
    {
        return [
            'category' => ProductNotificationCategory::class
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
