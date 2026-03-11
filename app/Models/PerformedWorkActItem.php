<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformedWorkActItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $table = 'sao_performed_work_act_items';

    /** Relations */
    public function performedWorkAct(): BelongsTo
    {
        return $this->belongsTo(PerformedWorkAct::class, 'work_act_id');
    }
}
