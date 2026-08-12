<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @property $id
 * @property $name
 * @property $time_zone
 * @property string $direct_budget_refresh_time
 * @property $url
 * @property $email
 * @property $phone
 * @property $address
 * @property ?string $logo_src
 * @property $created_at
 * @property $updated_at
 * @property Collection<AgencyUser> $admins
 */
class Agency extends Model
{
    use HasFactory;

    protected $table = 'agencies';

    /**
     * ID задаётся приложением (случайный 4-значный при создании), не AUTO_INCREMENT.
     */
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'time_zone',
        'direct_budget_refresh_time',
        'url',
        'email',
        'phone',
        'address',
        'logo_src',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
