<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Enums\Role as RoleEnum;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'first_name' => 'Николай',
            'last_name' => 'Корниенко',
            'login' => 'admin',
            'email' => 'admin@admin.ru',
            'phone' => '+7 (900) 123-45-67',
            'image_path' => null,
            'megaplan_id' => '1000272',
            'enable_important_notifications' => true,
            'enable_notifications' => true,
            'is_active' => true,
            'password' => Hash::make('123123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->assignRole(RoleEnum::ADMIN->value);
    }
}
