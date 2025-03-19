<?php

namespace App\Models;

use App\Enums\IntegrationCategory;
use Illuminate\Database\Eloquent\Model;

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
