<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformedWorkAct extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $table = 'sao_performed_work_acts';

    protected $casts = [
        'creation_date' => 'date',
    ];

    /** Relations */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_inn', 'inn');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PerformedWorkActItem::class, 'work_act_id');
    }
}
