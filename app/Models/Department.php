<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $name
 * @property $description
 */
class Department extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'description'];
}
