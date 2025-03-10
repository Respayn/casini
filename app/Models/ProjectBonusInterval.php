<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Условие расчета бонусов и/или гарантий для каждого проекта.
 *
 * @property $id
 * @property $project_bonus_condition_id
 * @property $from_percentage
 * @property $to_percentage
 * @property $bonus_amount
 * @property $bonus_percentage
 */
class ProjectBonusInterval extends Model
{
    protected $fillable = [
        'project_bonus_condition_id',
        'from_percentage',
        'to_percentage',
        'bonus_amount',
        'bonus_percentage',
    ];

    public function bonusCondition()
    {
        return $this->belongsTo(ProjectBonusCondition::class);
    }
}
