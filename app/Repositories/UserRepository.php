<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends EloquentRepository
{
    public function model(): string
    {
        return User::class;
    }
}
