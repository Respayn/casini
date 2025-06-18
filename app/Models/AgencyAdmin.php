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
    ];

    public function agency()
    {
        return $this->belongsTo(AgencySetting::class, 'agency_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
