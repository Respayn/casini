<?php

namespace App\Services;

use App\Data\UserData;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;

class UserService
{
    public function __construct(
        public UserRepository $repository
    ) {}

    /**
     * Получить всех пользователей агентства (можно только активных)
     * @return Collection<UserData>
     */
    public function getByAgency(int $agencyId, ?bool $onlyActive = null, array $with = []): Collection
    {
        return $this->repository->allByAgency($agencyId, $onlyActive, $with);
    }

    /**
     * Получить всех менеджеров агентства
     * @return Collection<UserData>
     */
    public function getManagers(int $agencyId, array $with = []): Collection
    {
        return $this->repository->allByAgency($agencyId, null, $with)
            ->filter(fn(UserData $user) => $user->roles && in_array('manager', $user->roles));
    }

    /**
     * Получить всех пользователей (без фильтрации)
     * @return Collection<UserData>
     */
    public function getAll(array $with = []): Collection
    {
        return $this->repository->all($with);
    }

    /**
     * Найти пользователя по id
     */
    public function find(int $id, array $with = []): ?UserData
    {
        return $this->repository->find($id, $with);
    }

    /**
     * Найти пользователя по логину
     */
    public function findByLogin(string $login, array $with = []): ?UserData
    {
        return $this->repository->findByLogin($login, $with);
    }
}
