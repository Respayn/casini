<?php

namespace App\Repositories;

use App\Data\UserData;
use App\Models\RateUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $with = array_merge($with, ['latestRate.rate']); // Жадная загрузка!
        $query = $this->queryWith($with)
            ->whereHas('agencies', fn($q) => $q->where('agency_id', $agencyId));

        if (!is_null($onlyActive)) {
            $query->where('is_active', $onlyActive);
        }

        return $query->get()->map(function ($user) {
            return new UserData(
                id: $user->id,
                login: $user->login,
                name: $user->name,
                email: $user->email,
                roles: $user->roles->pluck('name')->toArray(),
                first_name: $user->first_name,
                last_name: $user->last_name,
                is_active: $user->is_active,
                rate_name: optional($user->latestRate?->rate)->name, // вот так!
            );
        });
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

    /**
     * Создать пользователя и сохранить ставку (история в rate_user)
     *
     * @param array $data
     * @return User
     */
    public function createWithRate(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // 1. Создаем пользователя
            $user = User::create([
                'login'   => $data['login'],
                'name'    => $data['name'],
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'is_active'  => !empty($data['is_active']),
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'image_path' => $data['image_path'] ?? null,
                'megaplan_id' => $data['megaplan_id'] ?? null,
                'enable_important_notifications' => !empty($data['enable_important_notifications']),
                'enable_notifications' => !empty($data['enable_notifications']),
                'email_verified_at' => $data['email_verified_at'] ?? null,
                'password' => bcrypt($data['password'] ?? Str::random(12)),
            ]);

            // 2. Привязка к агентству (если нужно, иначе убери этот блок)
            if (!empty($data['agency_id'])) {
                // Если связь через "agencies"
                $user->agencies()->attach($data['agency_id']);
            }

            // 3. Сохраняем ставку (rate_user) — история!
            if (!empty($data['rate_id'])) {
                RateUser::create([
                    'user_id' => $user->id,
                    'rate_id' => $data['rate_id'],
                ]);
            }

            return $user;
        });
    }

    /**
     * Обновить пользователя и сохранить историю ставок (rate_user)
     *
     * @param int $userId
     * @param array $data
     * @return User
     */
    public function updateWithRate(int $userId, array $data): User
    {
        return DB::transaction(function () use ($userId, $data) {
            /** @var User|null $user */
            $user = User::findOrFail($userId);

            // 1. Обновляем поля пользователя
            $updateData = [
                'login'   => $data['login'],
                'name'    => $data['name'],
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'is_active'  => !empty($data['is_active']),
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'image_path' => $data['image_path'] ?? null,
                'megaplan_id' => $data['megaplan_id'] ?? null,
                'enable_important_notifications' => !empty($data['enable_important_notifications']),
                'enable_notifications' => !empty($data['enable_notifications']),
                'email_verified_at' => $data['email_verified_at'] ?? null,
            ];

            // Обновлять пароль, только если он был явно передан
            if (!empty($data['password'])) {
                $updateData['password'] = bcrypt($data['password']);
            }

            $user->update($updateData);

            // 2. Проверка: изменилась ли ставка?
            if (!empty($data['rate_id'])) {
                /** @var RateUser|null $latestRate */
                $latestRate = $user->rateUser()->latest('created_at')->first();
                if (!$latestRate || $latestRate->rate_id != $data['rate_id']) {
                    // 3. Новая ставка: сохраняем в истории (rate_user)
                    RateUser::create([
                        'user_id' => $user->id,
                        'rate_id' => $data['rate_id'],
                    ]);
                }
            }

            return $user;
        });
    }
}
