<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleSheetsMonthlySpending extends Model
{
    protected $fillable = [
        'project_id',
        'year_month',
        'programming_hours',
        'programming_sum',
        'copyrighting_units',
        'copyrighting_sum',
        'seo_links_sum',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'year_month' => 'date',
            'programming_hours' => 'float',
            'programming_sum' => 'float',
            'copyrighting_units' => 'float',
            'copyrighting_sum' => 'float',
            'seo_links_sum' => 'float',
            'synced_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
