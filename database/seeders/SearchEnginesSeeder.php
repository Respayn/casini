<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SearchEnginesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $existingCodes = DB::table('search_engines')->pluck('code')->toArray();

        $engines = [
            [
                'name' => 'Google',
                'code' => 'google',
                'base_url' => 'https://www.google.com',
            ],
            [
                'name' => 'Yandex',
                'code' => 'yandex',
                'base_url' => 'https://yandex.ru',
            ],
        ];

        foreach ($engines as $engine) {
            if (!in_array($engine['code'], $existingCodes)) {
                DB::table('search_engines')->insert(array_merge($engine, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
