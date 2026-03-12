<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserColumnPreference extends Model
{
    protected $fillable = [
        'table_id',
        'user_id',
        'settings'
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }
}
