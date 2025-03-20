<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Collection;

class UserService
{
    public function __construct(
      public UserRepository $repository
    ) {
    }

    /**
     * @return Collection<\App\Data\ClientData>
     */
    public function getManagers(array $with = []): Collection
    {
        // TODO: Получение менеджеров
        return collect($this->repository->all($with));
    }
}
