<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property $id
 * @property $name
 * @property $time_zone
 * @property $url
 * @property $email
 * @property $phone
 * @property $address
 * @property $logo_src
 * @property $created_at
 * @property $updated_at
 * @property Collection<AgencyAdmin> $admins
 */
class AgencySetting extends Model
{
    protected $table = 'agency_settings';
    protected $fillable = [
        'name',
        'time_zone',
        'url',
        'email',
        'phone',
        'address',
        'logo_src',
    ];

    public function admins()
    {
        return $this->hasMany(AgencyAdmin::class, 'agency_id');
    }
}
