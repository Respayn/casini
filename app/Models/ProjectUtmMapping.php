<?php

namespace App\Models;

use App\Enums\UtmType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $id
 * @property $project_id
 * @property $utm_type
 * @property $utm_value
 * @property $replacement_value
 * @property $created_at
 * @property $updated_at
 *
 * @property Project $project
 */
class ProjectUtmMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'utm_type',
        'utm_value',
        'replacement_value',
    ];

    protected $casts = [
        'utm_type' => UtmType::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
