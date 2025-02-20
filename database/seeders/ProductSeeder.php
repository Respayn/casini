<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'name' => 'Справочник клиентов',
                'code' => 'clients',
            ],
            [
                'name' => 'Статистика',
                'code' => 'statistics',
            ],
            [
                'name' => 'Сверка бюджетов',
                'code' => 'budget_control',
            ],
            [
                'name' => 'Каналы',
                'code' => 'channels',
            ],
            [
                'name' => 'ДРС',
                'code' => 'ad_movement',
            ],
            [
                'name' => 'Планирование',
                'code' => 'planning',
            ],
            [
                'name' => 'Медиапланирование',
                'code' => 'media_planning',
            ],
            [
                'name' => 'Отчеты',
                'code' => 'reports',
            ],
            [
                'name' => 'Шаблоны отчетов',
                'code' => 'report_templates',
            ]
        ]);
    }
}
