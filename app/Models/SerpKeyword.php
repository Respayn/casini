<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $project_id
 * @property string $phrase
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\Project $project
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\SerpTask> $tasks
 */
class SerpKeyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'phrase',
    ];

    /**
     * Проект, к которому относится ключевая фраза.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Задачи мониторинга, использующие эту фразу.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(SerpTask::class);
    }
}