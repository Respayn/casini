<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rate extends Model
{
    protected $fillable = [
        'name',
        'fetch_spendings_time'
    ];

    protected $casts = [
        'fetch_spendings_time' => 'boolean'
    ];

    public function values(): HasMany
    {
        return $this->hasMany(RateValue::class, 'rate_id', 'id');
    }
}
