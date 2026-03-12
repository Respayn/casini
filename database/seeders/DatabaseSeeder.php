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
            AgencySettingsTableSeeder::class,
            RatesTableSeeder::class,
            SearchEnginesSeeder::class
            ManagersSeeder::class,
            ChannelsSeeder::class,
            PaymentsSeeder::class,
            PaymentOperationsSeeder::class,
            SaoPerformedWorkActsSeeder::class,
            SaoPerformedWorkActItemsSeeder::class

        ]);
    }
}
