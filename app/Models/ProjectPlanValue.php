<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPlanValue extends Model
{
    protected $fillable = [
        'project_id',
        'parameter_code',
        'value',
        'year_month_date'
    ];

    protected $casts = [
        'year_month_date' => 'datetime'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
