<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    public function template(): HasOne
    {
        return $this->hasOne(Template::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function specialist(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'specialist_id');
    }

    public function manager(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'manager_id');
    }
}
