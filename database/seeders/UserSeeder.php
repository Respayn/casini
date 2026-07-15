<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Agency;
use Illuminate\Database\Seeder;
use App\Enums\Role as RoleEnum;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['login' => 'admin'],
            [
                'first_name' => 'Николай',
                'last_name' => 'Корниенко',
                'email' => 'admin@admin.ru',
                'phone' => '+7 (900) 123-45-67',
                'image_path' => null,
                'megaplan_id' => '1000272',
                'enable_important_notifications' => true,
                'enable_notifications' => true,
                'is_active' => true,
                'password' => '123123',
            ]
        );

        $user->assignRole(RoleEnum::ADMIN->value);

        $agency = Agency::query()->first();

        if ($agency === null) {
            $agency = Agency::create([
                'name' => 'СайтАктив',
                'time_zone' => 'Europe/Moscow',
                'url' => 'https://siteactiv.ru',
                'email' => 'info@siteactiv.ru',
                'phone' => '+73433172230',
                'address' => 'г. Екатеринбург, ул. Примерная, 1',
                'logo_src' => null,
            ]);
        }

        $user->agencies()->syncWithoutDetaching([$agency->id]);
    }
}
