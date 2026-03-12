<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property \Carbon\Carbon $month
 * @property string $city
 * @property int $visits
 * @property int $visitors
 * @property int $goal_reaches
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\Project $project
 */
class YandexMetrikaVisitsGeo extends Model
{
    use HasFactory;

    protected $table = 'yandex_metrika_visits_geo';

    protected $fillable = [
        'project_id',
        'month',
        'city',
        'visits',
        'visitors',
        'goal_reaches',
    ];

    protected $casts = [
        'month' => 'date',
    ];

    /**
     * Проект, к которому относится статистика.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
