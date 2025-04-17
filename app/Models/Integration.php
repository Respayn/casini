<?php

namespace App\Models;

use App\Enums\IntegrationCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $created_at
 * @property $updated_at
 * @property $name
 * @property $category
 * @property $notification
 * @property $code
 */
class Integration extends Model
{
    protected $fillable = [
        'name',
        'category',
        'notification',
        'code'
    ];

    protected $casts = [
        'category' => IntegrationCategory::class
    ];
}
