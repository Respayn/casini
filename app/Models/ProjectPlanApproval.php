<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPlanApproval extends Model
{
    protected $fillable = [
        'project_id',
        'period',
        'year',
        'period_number',
        'approved'
    ];

    protected $casts = [
        'approved' => 'boolean'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
