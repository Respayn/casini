<?php

namespace App\Models;

use App\Enums\IntegrationSyncItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationSyncItem extends Model
{
    protected $fillable = [
        'run_id',
        'project_id',
        'collector',
        'status',
        'attempts',
        'position',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'status' => IntegrationSyncItemStatus::class,
            'attempts' => 'integer',
            'position' => 'integer',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(IntegrationSyncRun::class, 'run_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
