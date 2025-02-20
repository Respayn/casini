<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'is_restricted',
        'notification',
        'code'
    ];

    public function productNotifications(): HasMany
    {
        return $this->hasMany(ProductNotification::class, 'product_id', 'id');
    }
}
