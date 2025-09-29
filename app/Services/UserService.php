<?php

namespace App\Services;

use App\Data\UserData;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
    public function getManagers(?int $agencyId = null, array $with = []): Collection
    {
        if (!$agencyId) {
            $agencyId = Auth::user()->agencies()->first()->id;
        }
        return $this->repository->allByAgency($agencyId, null, $with)
            ->filter(fn(UserData $user) => $user->roles->contains(fn($role) => $role->use_in_managers_list))
            ->values();
    }

    public function getSpecialists(?int $agencyId = null, array $with = []): Collection
    {
        if (!$agencyId) {
            $agencyId = Auth::user()->agencies()->first()->id;
        }
        return $this->repository->allByAgency($agencyId, null, $with)
            ->filter(fn(UserData $user) => $user->roles->contains(fn($role) => $role->use_in_specialist_list))
            ->values();
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

    /**
     * Создать пользователя с историей ставок
     * @param array $data
     * @return UserData
     */
    public function create(array $data): UserData
    {
        $user = $this->repository->createWithRate($data);
        return UserData::fromLivewire($user);
    }

    /**
     * Обновить пользователя и сохранить историю ставок
     * @param int $userId
     * @param array $data
     * @return UserData
     */
    public function update(int $userId, array $data): UserData
    {
        $user = $this->repository->updateWithRate($userId, $data);
        return UserData::fromLivewire($user);
    }
}
