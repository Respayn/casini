<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $project_id
 * @property $external_id
 * @property $date
 * @property $utm_source
 * @property $utm_campaign
 * @property $utm_medium
 * @property $utm_content
 * @property $utm_term
 * @property $created_at
 * @property $updated_at
 */
class CallibriLead extends Model
{
    protected $fillable = [
        'project_id',
        'external_id',
        'date',
        'utm_source',
        'utm_campaign',
        'utm_medium',
        'utm_content',
        'utm_term',
    ];

    protected $casts = [
        'date' => 'datetime'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
