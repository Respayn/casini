<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = [
        "name",
        "search_string",
    ];

    public static function getDefault(): Channel|null
    {
        return self::whereNull('search_string')->first();
    }
}
