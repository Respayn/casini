<?php

namespace Src\Infrastructure\Persistence;

use App\Models\User as EloquentUser;
use Src\Domain\Users\User;
use Src\Domain\Users\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): User
    {
        $eloquentUser = EloquentUser::find($id);
        return $this->mapToEntity($eloquentUser);
    }

    private function mapToEntity(EloquentUser $user): User
    {
        return new User(
            $user->id,
            $user->first_name,
            $user->last_name,
            $user->email,
            $user->phone
        );
    }
}