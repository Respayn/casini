<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Админ',
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
    }
}
