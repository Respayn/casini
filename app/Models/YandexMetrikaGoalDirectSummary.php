<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property string $goal_name
 * @property \Carbon\Carbon $month
 * @property int $conversions
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\Project $project
 */
class YandexMetrikaGoalDirectSummary extends Model
{
    use HasFactory;

    protected $table = 'yandex_metrika_goal_direct_summary';

    protected $fillable = [
        'project_id',
        'goal_name',
        'month',
        'conversions',
    ];

    protected $casts = [
        'month' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
