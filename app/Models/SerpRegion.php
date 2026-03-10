<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $search_engine_id
 * @property string $name
 * @property string $code
 * @property string $language
 * @property string $country_code
 * @property string $geo_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \App\Models\SearchEngine $searchEngine
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\SerpTask> $tasks
 */
class SerpRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'search_engine_id',
        'name',
        'code',
        'language',
        'country_code',
        'geo_id',
    ];

    /**
     * Поисковая система, к которой привязан регион.
     */
    public function searchEngine(): BelongsTo
    {
        return $this->belongsTo(SearchEngine::class);
    }

    /**
     * Задачи мониторинга, использующие этот регион.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(SerpTask::class);
    }
}