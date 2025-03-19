<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $inn
 * @property double $initial_balance
 * @property int $manager_id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property User $manager
 * @property Collection<Project> $project
 */
class Client extends Model
{
    use HasFactory;

    protected $fillable = [
      'name',
      'inn',
      'initial_balance',
      'manager_id',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2'
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }
}
