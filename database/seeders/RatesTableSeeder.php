<?php

namespace Database\Seeders;

use App\Models\Rate;
use App\Models\RateValue;
use Illuminate\Database\Seeder;

class RatesTableSeeder extends Seeder
{
    public function run()
    {
        $defaultValues = [
            'Базовая ставка' => 800.00,
            'Ставка seo-специалиста' => 1200.00,
            'Ставка помощника seo' => 600.00,
            'PPC-специалист' => 1300.00,
            'Неактивен' => 0.00,
            'Бухгалтер' => 900.00,
        ];

        foreach ($defaultValues as $rateName => $value) {
            $rate = Rate::firstOrCreate(['name' => $rateName]);
            RateValue::firstOrCreate([
                'rate_id' => $rate->id,
                'value' => $value,
            ], [
                'start_date' => now(),
            ]);
        }
    }
}
