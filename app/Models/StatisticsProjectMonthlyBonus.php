<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Снимок колонки «Бонусы и гарантии» в Статистике за месяц.
 *
 * @property int $id
 * @property int $project_id
 * @property int $year
 * @property int $month
 * @property string $kind
 * @property float|null $value
 * @property Carbon $calculated_at
 */
class StatisticsProjectMonthlyBonus extends Model
{
    protected $fillable = [
        'project_id',
        'year',
        'month',
        'kind',
        'value',
        'calculated_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'value' => 'float',
        'calculated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return array{kind: string, value?: float}
     */
    public function toBonusPayload(): array
    {
        $payload = ['kind' => $this->kind];
        if ($this->kind === 'amount') {
            $payload['value'] = (float) ($this->value ?? 0);
        }

        return $payload;
    }
}
