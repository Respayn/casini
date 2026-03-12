<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $agency_id
 * @property $user_id
 * @property $name
 * @property $created_at
 * @property $updated_at
 * @property Agency $agency
 */
class AgencyUser extends Model
{
    protected $table = 'agency_user';
    protected $fillable = [
        'agency_id',
        'user_id',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
