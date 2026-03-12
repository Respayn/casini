<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property \Carbon\Carbon $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property Project $project
 */
class CompletedWork extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
