<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Models\User;

class UserRepository extends EloquentRepository
{
    public function model(): string
    {
        return User::class;
    }

    public function all(array $with = [])
    {
        return UserData::collect(User::with($with)->get());
    }
}
