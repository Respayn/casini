<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

/**
 * @property $id
 * @property $project_id
 * @property $changed_by
 * @property Date $changed_at
 * @property $field
 * @property $old_value
 * @property $new_value
 * @property Project $project
 * @property User $changedBy
 */
class ProjectFieldHistory extends Model
{
    protected $fillable = [
        'project_id',
        'changed_by',
        'changed_at',
        'field',
        'old_value',
        'new_value',
    ];

    public $timestamps = false;

    protected array $dates = ['changed_at'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
