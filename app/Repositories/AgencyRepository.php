<?php

namespace App\Repositories;


use App\Data\AgencyData;
use App\Livewire\Forms\SystemSettings\Agency\AgencySettingsForm;
use App\Models\Agency;
use App\Repositories\Interfaces\AgencyRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AgencyRepository extends EloquentRepository implements AgencyRepositoryInterface
{
    public function model()
    {
        return Agency::class;
    }

    public function all(array $with = ['users'])
    {
        $agencies = Agency::with($with)->get();

        return AgencyData::collect($agencies->map(function ($agency) {
            $arr = $agency->toArray();
            $arr['users'] = $agency->users;
            return $arr;
        })->all());
    }

    public function find(int $id): ?AgencyData
    {
        $agency = Agency::with(['users'])->find($id);

        if ($agency) {
            $arr = $agency->toArray();
            $arr['users'] = $agency->users;
            return AgencyData::from($arr);
        }

        return null;
    }

    public function findBy(string $column, mixed $value)
    {
        $agencies = Agency::where($column, $value)->with('users')->get();

        return AgencyData::collect($agencies->map(function ($agency) {
            $arr = $agency->toArray();
            $arr['users'] = $agency->users;
            return $arr;
        })->all());
    }

    /**
     * @throws \Throwable
     */
    public function saveAgency(AgencyData|AgencySettingsForm $data): void
    {
        // Приводим к массиву данных
        if ($data instanceof AgencySettingsForm) {
            $data = $data->toArray();
        } elseif ($data instanceof AgencyData) {
            $data = $data->toArray();
        }

        // Находим агентство по id
        $agency = Agency::findOrFail($data['id']);

        $lastLogoSrc = $agency->logo_src;

        DB::transaction(function () use ($lastLogoSrc, $data, $agency) {
            // Обновляем поля
            $agency->update([
                'name'      => $data['name'],
                'time_zone' => $data['timeZone'],
                'url'       => $data['url'] ?? null,
                'email'     => $data['email'] ?? null,
                'phone'     => $data['phone'] ?? null,
                'address'   => $data['address'] ?? null,
                'logo_src'  => $data['logoSrc'] ?? null,
            ]);

            if (!empty($lastLogoSrc)) {
                Storage::disk('public')->delete($lastLogoSrc);
            }
        });
    }

    public function getAgency(int $id): AgencyData
    {
        $agency = Agency::with(['users'])->findOrFail($id);

        // Подготавливаем массив админов
        $users = $agency->users->map(function ($user) {
            $name = trim("{$user->first_name} {$user->last_name}");
            return [
                'id' => $user->id,
                'name' => empty($name) ? $user->login : $name,
            ];
        });

        // Собираем массив для DTO
        $data = [
            'id' => $agency->id,
            'name' => $agency->name,
            'users' => $users,
            'timeZone' => $agency->time_zone,
            'url' => $agency->url,
            'email' => $agency->email,
            'phone' => $agency->phone,
            'address' => $agency->address,
            'logoSrc' => $agency->logo_src,
        ];

        return AgencyData::from($data);
    }

    public function createAgency($data): AgencyData
    {
        // 1. Преобразуем в массив (как раньше)
        if ($data instanceof AgencySettingsForm) {
            $data = $data->toArray();
        } elseif ($data instanceof AgencyData) {
            $data = $data->toArray();
        }

        // 2. Создаём агентство
        $agency = Agency::create([
            'name'      => $data['name'],
            'time_zone' => $data['timeZone'],
            'url'       => $data['url'] ?? null,
            'email'     => $data['email'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'address'   => $data['address'] ?? null,
        ]);

        // 3. Получаем текущего пользователя
        $user = Auth::user();

        // 4. Связываем пользователя с агентством как администратора
        if ($user) {
            $agency->users()->create([
                'user_id' => $user->id,
            ]);
        }

        // 5. Для DTO: обновляем массив админов и timeZone
        $agencyArr = $agency->toArray();
        $agencyArr['users'] = $agency->users()->get()->map(function($admin) {
            return [
                'id' => $admin->user_id,
            ];
        })->toArray();
        $agencyArr['timeZone'] = $agency->time_zone;

        return AgencyData::from($agencyArr);
    }
}
