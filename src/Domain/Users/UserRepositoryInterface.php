<?php

namespace Src\Domain\Users;

interface UserRepositoryInterface
{
    public function findById(int $id): User;
}
