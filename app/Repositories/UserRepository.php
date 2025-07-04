<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Models\User;
use Illuminate\Support\Collection;

class UserRepository extends EloquentRepository
{
    protected array $defaultWith = ['roles'];

    public function model(): string
    {
        return User::class;
    }

    protected function queryWith(array $with = [])
    {
        $with = array_unique(array_merge($this->defaultWith, $with));
        return parent::queryWith($with);
    }

    /**
     * Получить всех пользователей (UserData-коллекция)
     */
    public function all(array $with = []): Collection
    {
        return UserData::collect(
            $this->queryWith($with)->get()
        );
    }

    /**
     * Получить пользователей агентства (и только активных, если нужно)
     */
    public function allByAgency(int $agencyId, ?bool $onlyActive = null, array $with = []): Collection
    {
        $query = $this->queryWith($with)
            ->whereHas('agencies', fn($q) => $q->where('agency_id', $agencyId));

        if (!is_null($onlyActive)) {
            $query->where('is_active', $onlyActive);
        }

        return UserData::collect($query->get());
    }

    /**
     * Найти пользователя по id (UserData или null)
     */
    public function find(int $id, array $with = []): ?UserData
    {
        $user = $this->queryWith($with)->find($id);
        return $user ? UserData::fromLivewire($user) : null;
    }

    /**
     * Найти пользователя по логину (UserData или null)
     */
    public function findByLogin(string $login, array $with = []): ?UserData
    {
        $user = $this->queryWith($with)->where('login', $login)->first();
        return $user ? UserData::fromLivewire($user) : null;
    }

    /**
     * Получить активного пользователя по email
     */
    public function findActiveByEmail(string $email, array $with = []): ?UserData
    {
        $user = $this->queryWith($with)
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        return $user ? UserData::fromLivewire($user) : null;
    }
}
