<?php

namespace Database\Seeders;

use App\Models\Agency;
use Illuminate\Database\Seeder;

class AgencySettingsTableSeeder extends Seeder
{
    public function run()
    {
        Agency::firstOrCreate([
            'name' => 'СайтАктив',
            'time_zone' => 'Europe/Moscow',
            'url' => 'https://siteactiv.ru',
            'email' => 'info@siteactiv.ru',
            'phone' => '+73433172230',
            'address' => 'г. Екатеринбург, ул. Примерная, 1',
            'logo_src' => null,
        ]);
    }
}
