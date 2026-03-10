<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $project_id
 * @property int $serp_keyword_id
 * @property int $search_engine_id
 * @property int $serp_region_id
 * @property bool $is_active
 * @property string $check_frequency
 * @property \Carbon\Carbon|null $last_check_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\Project $project
 * @property \App\Models\SerpKeyword $keyword
 * @property \App\Models\SearchEngine $searchEngine
 * @property \App\Models\SerpRegion $region
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\SerpPosition> $positions
 */
class SerpTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'serp_keyword_id',
        'search_engine_id',
        'serp_region_id',
        'is_active',
        'check_frequency',
        'last_check_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_check_at' => 'datetime',
    ];

    /**
     * Проект, для которого выполняется проверка.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Ключевая фраза для снятия позиции.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(SerpKeyword::class, 'serp_keyword_id');
    }

    /**
     * Поисковая система.
     */
    public function searchEngine(): BelongsTo
    {
        return $this->belongsTo(SearchEngine::class);
    }

    /**
     * Регион поиска.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(SerpRegion::class, 'serp_region_id');
    }

    /**
     * Результаты проверок позиций.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(SerpPosition::class);
    }
}