<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ManagersSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('managers')->delete();
        
        \DB::table('managers')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Уфимцева Юлия',
            'phone' => '+7 (343) 344-96-20 (#112)',
                'email' => 'razvitie@siteactiv.ru',
                'created_at' => '2026-03-05 15:26:26',
                'updated_at' => '2026-03-05 15:26:29',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Лудищева Юлия',
            'phone' => '+7 (343) 344-96-20 (#115)',
                'email' => 'j.ludischeva@siteactiv.ru',
                'created_at' => '2026-03-05 15:26:28',
                'updated_at' => '2026-03-05 15:26:31',
            ),
        ));
        
        
    }
}