<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $serp_task_id
 * @property \Carbon\Carbon $check_date
 * @property int|null $position
 * @property string|null $url
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\SerpTask $task
 */
class SerpPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'serp_task_id',
        'check_date',
        'position',
        'url',
    ];

    protected $casts = [
        'check_date' => 'date',
    ];

    /**
     * Задача мониторинга, к которой относится позиция.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(SerpTask::class);
    }
}