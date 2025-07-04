<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $user_id
 * @property $rate_id
 * @property $created_at
 * @property $updated_at
 */
class RateUser extends Model
{
    protected $table = 'rate_user';

    protected $fillable = [
        'user_id',
        'rate_id',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rate()
    {
        return $this->belongsTo(Rate::class);
    }
}
