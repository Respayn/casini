<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateValue extends Model
{
    protected $fillable = [
        'rate_id',
        'value',
        'start_date',
        'end_date'
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date'
        ];
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(Rate::class);
    }
}
