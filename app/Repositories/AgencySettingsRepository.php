<?php

namespace App\Repositories;


use App\Data\AgencyData;
use App\Livewire\Forms\SystemSettings\Agency\AgencySettingsForm;
use App\Models\AgencySetting;
use App\Repositories\Interfaces\AgencySettingsRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AgencySettingsRepository extends EloquentRepository implements AgencySettingsRepositoryInterface
{
    public function model()
    {
        return AgencySetting::class;
    }

    public function all(array $with = ['admins'])
    {
        $agencies = AgencySetting::with($with)->get();

        return AgencyData::collect($agencies->map(function ($agency) {
            $arr = $agency->toArray();
            $arr['admins'] = $agency->admins;
            return $arr;
        })->all());
    }

    public function find(int $id): ?AgencyData
    {
        $agency = AgencySetting::with('admins')->find($id);

        if ($agency) {
            $arr = $agency->toArray();
            $arr['admins'] = $agency->admins;
            return AgencyData::from($arr);
        }

        return null;
    }

    public function findBy(string $column, mixed $value)
    {
        $agencies = AgencySetting::where($column, $value)->with('admins')->get();

        return AgencyData::collect($agencies->map(function ($agency) {
            $arr = $agency->toArray();
            $arr['admins'] = $agency->admins;
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
        $agency = AgencySetting::findOrFail($data['id']);

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
                \Storage::disk('public')->delete($lastLogoSrc);
            }
        });

        // Если нужны админы:
        if (!empty($data['admins'])) {
            // Удаляем старых
            $agency->admins()->delete();

            // Добавляем новых
            foreach ($data['admins'] as $admin) {
                $agency->admins()->create([
                    'user_id' => $admin['id'],
                    'name'    => $admin['name'],
                ]);
            }
        }
    }

    public function getAgency(int $id): AgencyData
    {
        $agency = AgencySetting::with('admins')->findOrFail($id);

        // Подготавливаем массив админов
        $admins = $agency->admins->map(function ($admin) {
            return [
                'id' => $admin->user_id,
                'name' => $admin->name,
            ];
        });

        // Собираем массив для DTO
        $data = [
            'id' => $agency->id,
            'name' => $agency->name,
            'admins' => $admins,
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
        $agency = AgencySetting::create([
            'name'      => $data['name'],
            'time_zone' => $data['timeZone'],
            'url'       => $data['url'] ?? null,
            'email'     => $data['email'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'address'   => $data['address'] ?? null,
        ]);

        // 3. Получаем текущего пользователя
        $user = auth()->user();

        // 4. Связываем пользователя с агентством как администратора
        if ($user) {
            $agency->admins()->create([
                'user_id' => $user->id,
                'name' => $user->name,
            ]);
        }

        // 5. Для DTO: обновляем массив админов и timeZone
        $agencyArr = $agency->toArray();
        $agencyArr['admins'] = $agency->admins()->get()->map(function($admin) {
            return [
                'id' => $admin->user_id,
                'name' => $admin->name,
            ];
        })->toArray();
        $agencyArr['timeZone'] = $agency->time_zone;

        return AgencyData::from($agencyArr);
    }
}
