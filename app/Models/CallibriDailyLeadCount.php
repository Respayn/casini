<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallibriDailyLeadCount extends Model
{
    protected $table = 'callibri_daily_lead_counts';

    protected $fillable = [
        'project_id',
        'date',
        'leads_count',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'leads_count' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
