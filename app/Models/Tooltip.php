<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tooltip extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'path',
        'label',
        'content'
    ];
}
