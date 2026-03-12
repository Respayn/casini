<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $base_url
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\SerpRegion> $regions
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\SerpTask> $tasks
 */
class SearchEngine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'base_url',
    ];

    /**
     * Регионы поиска, привязанные к этой поисковой системе.
     */
    public function regions(): HasMany
    {
        return $this->hasMany(SerpRegion::class);
    }

    /**
     * Задачи мониторинга, использующие эту поисковую систему.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(SerpTask::class);
    }
}