<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $project_id
 * @property string $campaign_name
 * @property int|null $campaign_id
 * @property int $impressions
 * @property int $clicks
 * @property float $cost_with_vat
 * @property float $cost_without_vat
 * @property int $conversions
 * @property string|null $goal_name
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class YandexDirectCampaignStats extends Model
{
    protected $fillable = [
        'project_id',
        'campaign_name',
        'campaign_id',
        'impressions',
        'clicks',
        'cost_with_vat',
        'cost_without_vat',
        'conversions',
        'goal_name',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'cost_with_vat' => 'decimal:2',
        'cost_without_vat' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
