<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'id' => 1,
                'name' => 'seo-department',
                'description' => 'SEO-продвижение',
            ],
            [
                'id' => 2,
                'name' => 'kr-department',
                'description' => 'Контекстная реклама',
            ],
        ];

        DB::table('departments')->insert($departments);
    }
}
