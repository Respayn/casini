<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IntegrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('integrations')->insert([
            [
                'name' => '1С акты',
                'category' => 'Деньги',
                'code' => '1c_acts'
            ],
            [
                'name' => '1С движение рекламных средств',
                'category' => 'Деньги',
                'code' => '1c_ad_budget_flow'
            ],
            [
                'name' => '1С сверка',
                'category' => 'Деньги',
                'code' => '1c_check'
            ],
            [
                'name' => 'Yandex Search API',
                'category' => 'Аналитика',
                'code' => 'yandex_search_api'
            ],
            [
                'name' => 'Google Таблицы',
                'category' => 'Аналитика',
                'code' => 'google__sheets'
            ],
            [
                'name' => 'Мегаплан',
                'category' => 'Аналитика',
                'code' => 'megaplan'
            ],
            [
                'name' => 'Яндекс Директ',
                'category' => 'Инструменты',
                'code' => 'yandex_direct'
            ],
            [
                'name' => 'Яндекс Метрика',
                'category' => 'Аналитика',
                'code' => 'yandex_metrika'
            ],
            [
                'name' => 'Callibri',
                'category' => 'Аналитика',
                'code' => 'callibri'
            ]
        ]);
    }
}
