<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $agency_id
 * @property $user_id
 * @property $name
 * @property $created_at
 * @property $updated_at
 * @property AgencySetting $agency
 */
class AgencyAdmin extends Model
{
    protected $table = 'agency_admins';
    protected $fillable = [
        'agency_id',
        'user_id',
        'name',
    ];

    public function agency()
    {
        return $this->belongsTo(AgencySetting::class, 'agency_id');
    }
}
