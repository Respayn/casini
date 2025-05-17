<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id
 * @property $work_act_id
 * @property $number
 * @property $name
 * @property $quantity
 * @property $unit
 * @property $price
 * @property $created_at
 * @property $updated_at
 *
 * @property SaoPerformedWorkAct $workAct
 */
class SaoPerformedWorkActItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name',
        'quantity',
        'unit',
        'price'
    ];

    protected $casts = [
        'quantity' => 'float',
        'price' => 'float'
    ];

    public function workAct(): BelongsTo
    {
        return $this->belongsTo(SaoPerformedWorkAct::class, 'work_act_id');
    }
}
