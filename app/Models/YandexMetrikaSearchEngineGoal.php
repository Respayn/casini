<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property string $search_engine
 * @property int $goal_id
 * @property string $goal_name
 * @property Carbon $month
 * @property int $conversions
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Project $project
 */
class YandexMetrikaSearchEngineGoal extends Model
{
    protected $fillable = [
        'project_id',
        'search_engine',
        'goal_id',
        'goal_name',
        'month',
        'conversions',
    ];

    protected $casts = [
        'month' => 'date',
        'goal_id' => 'integer',
        'conversions' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
