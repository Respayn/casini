<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Общие настройки бонусов для каждого проекта.
 *
 * @property $id
 * @property $project_id
 * @property $bonuses_enabled
 * @property $calculate_in_percentage
 * @property $client_payment
 * @property $start_month
 */
class ProjectBonusCondition extends Model
{
    protected $fillable = [
        'project_id',
        'bonuses_enabled',
        'calculate_in_percentage',
        'client_payment',
        'start_month',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function intervals()
    {
        return $this->hasMany(ProjectBonusInterval::class);
    }
}
