<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projectTypes = [
            ['id' => 1, 'name' => 'Корп. сайт'],
            ['id' => 2, 'name' => 'Интернет-магазин'],
            ['id' => 3, 'name' => 'Landing-page'],
            ['id' => 4, 'name' => 'Портал'],
        ];

        // Вставка данных в таблицу
        DB::table('project_types')->insert($projectTypes);
    }
}
