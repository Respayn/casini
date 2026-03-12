<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property string $goal_name
 * @property \Carbon\Carbon $achieved_date
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_content
 * @property string|null $utm_term
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\Project $project
 */
class YandexMetrikaGoalUtm extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'goal_name',
        'achieved_date',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    protected $casts = [
        'achieved_date' => 'date',
    ];

    /**
     * Проект, к которому относятся данные.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
