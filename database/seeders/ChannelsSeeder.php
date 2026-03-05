<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChannelsSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('channels')->delete();
        
        \DB::table('channels')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Яндекс Директ',
                'search_string' => 'Рекламный бюджет Яндекс Директ',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'ВКонтакте',
                'search_string' => 'Рекламный бюджет Вконтакте',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'Google Ads',
                'search_string' => 'Рекламный бюджет Google Ads',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'Яндекс Геомедийная реклама',
                'search_string' => 'Рекламный бюджет Яндекс Геомедийная реклама',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'Яндекс Карты',
                'search_string' => 'Рекламный бюджет Яндекс Карты',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'Яндекс медийная реклама',
                'search_string' => 'Рекламный бюджет Яндекс медийная реклама',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'Яндекс Бизнес',
                'search_string' => 'Рекламный бюджет Яндекс Бизнес',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'Вконтакте медийная реклама',
                'search_string' => 'Рекламный бюджет Вконтакте медийная реклама',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            8 => 
            array (
                'id' => 9,
                'name' => 'Авито',
                'search_string' => 'Рекламный бюджет Авито',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            9 => 
            array (
                'id' => 10,
                'name' => 'Авито медийная реклама',
                'search_string' => 'Рекламный бюджет Авито медийная реклама',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            10 => 
            array (
                'id' => 11,
                'name' => 'myTarget',
                'search_string' => 'Рекламный бюджет myTarget',
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
            11 => 
            array (
                'id' => 12,
                'name' => 'Прочее',
                'search_string' => NULL,
                'created_at' => '2026-03-03 12:04:05',
                'updated_at' => '2026-03-03 12:04:05',
            ),
        ));
        
        
    }
}