<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionTopic extends Model
{
    protected $fillable = [
        'category',
        'topic'
    ];
}
