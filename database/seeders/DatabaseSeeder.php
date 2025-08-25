<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            TooltipSeeder::class,
            ProductSeeder::class,
            ProductNotificationSeeder::class,
            IntegrationSeeder::class,
            AgencySettingsTableSeeder::class,
            RatesTableSeeder::class,
        ]);
    }
}
