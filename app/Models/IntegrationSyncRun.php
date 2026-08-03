<?php

namespace App\Models;

use App\Enums\IntegrationSyncRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationSyncRun extends Model
{
    protected $fillable = [
        'local_date',
        'timezone',
        'target_date',
        'status',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'local_date' => 'date',
            'target_date' => 'date',
            'status' => IntegrationSyncRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(IntegrationSyncItem::class, 'run_id');
    }
}
