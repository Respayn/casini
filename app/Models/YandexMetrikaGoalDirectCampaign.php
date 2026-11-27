<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property string $goal_name
 * @property int $campaign_id
 * @property string $campaign_name
 * @property Carbon $month
 * @property int $conversions
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Project $project
 */
class YandexMetrikaGoalDirectCampaign extends Model
{
    protected $table = 'yandex_metrika_goal_direct_campaigns';

    protected $fillable = [
        'project_id',
        'goal_name',
        'campaign_id',
        'campaign_name',
        'month',
        'conversions',
    ];

    protected $casts = [
        'month' => 'date',
        'campaign_id' => 'integer',
        'conversions' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
