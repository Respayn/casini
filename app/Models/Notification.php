<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id','text','link_url','type','payload','project_id','read_at'
    ];
    protected $casts = [
        'payload' => 'array',
        'read_at' => 'datetime',
    ];
}
