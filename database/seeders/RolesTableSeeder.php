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
            'admin' => 'Администратор',
            'manager' => 'Менеджер',
            'kr' => 'Специалист (директолог)',
            'seo' => 'Специалист (SEO)',
            'rucovotdelseo' => 'Руководитель SEO отдела',
            'rucovotdelkp' => 'Руководитель KP отдела',
            'rucovotdelmanager' => 'Руководитель отдела менеджеров',
            'office_manager' => 'Офис-менеджер',
        ];

        foreach ($roles as $name => $displayName) {
            SpatieRole::updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName]
            );
        }
    }
}
