<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Enums\Role as RoleEnum;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'admin' => [
                'displayName' => 'Администратор',
                'useInManagersList' => false,
                'useInSpecialistList' => false
            ],
            'manager' => [
                'displayName' => 'Менеджер',
                'useInManagersList' => true,
                'useInSpecialistList' => false
            ],
            'kr' => [
                'displayName' => 'Специалист (директолог)',
                'useInManagersList' => false,
                'useInSpecialistList' => true
            ],
            'seo' => [
                'displayName' => 'Специалист (SEO)',
                'useInManagersList' => false,
                'useInSpecialistList' => true
            ],
            'rucovotdelseo' => [
                'displayName' => 'Руководитель SEO отдела',
                'useInManagersList' => false,
                'useInSpecialistList' => true
            ],
            'rucovotdelkp' => [
                'displayName' => 'Руководитель KP отдела',
                'useInManagersList' => false,
                'useInSpecialistList' => true
            ],
            'rucovotdelmanager' => [
                'displayName' => 'Руководитель отдела менеджеров',
                'useInManagersList' => true,
                'useInSpecialistList' => false
            ],
            'office_manager' => [
                'displayName' => 'Офис-менеджер',
                'useInManagersList' => false,
                'useInSpecialistList' => false
            ],
        ];

        foreach ($roles as $name => $roleData) {
            SpatieRole::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $roleData['displayName'],
                    'use_in_managers_list' => $roleData['useInManagersList'],
                    'use_in_specialist_list' => $roleData['useInSpecialistList'],
                ]
            );
        }
    }
}
