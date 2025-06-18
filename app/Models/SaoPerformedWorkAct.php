<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $number
 * @property Carbon $creation_date
 * @property float $price
 * @property string|null $customer_inn
 * @property string|null $contract_number
 * @property string|null $customer_additional_number
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 *
 * @property Project $project
 * @property SaoPerformedWorkActItem[] $items
 */
class SaoPerformedWorkAct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'number',
        'creation_date',
        'price',
        'customer_inn',
        'contract_number',
        'customer_additional_number'
    ];

    protected $casts = [
        'creation_date' => 'date',
        'price' => 'float'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaoPerformedWorkActItem::class, 'work_act_id');
    }
}
