<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property Carbon $date
 * @property float $top_10_percent
 * @property int $phrases_count
 */
class YandexSearchApiDailyTopPercent extends Model
{
    protected $table = 'yandex_search_api_daily_top_percents';

    protected $fillable = [
        'project_id',
        'date',
        'top_10_percent',
        'phrases_count',
    ];

    protected $casts = [
        'date' => 'date',
        'top_10_percent' => 'float',
        'phrases_count' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function upsertDaily(
        int $projectId,
        string $date,
        float $top10Percent,
        int $phrasesCount,
    ): void {
        static::query()->updateOrCreate(
            [
                'project_id' => $projectId,
                'date' => $date,
            ],
            [
                'top_10_percent' => round($top10Percent, 1),
                'phrases_count' => $phrasesCount,
            ]
        );
    }
}
