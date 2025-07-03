<?php

namespace Database\Seeders;

use App\Models\Rate;
use App\Models\RateValue;
use Illuminate\Database\Seeder;

class RatesTableSeeder extends Seeder
{
    public function run()
    {
        $rate = Rate::create(['name' => 'Базовая ставка']);
        RateValue::create([
            'rate_id' => $rate->id,
            'value' => 2358.00,
            'start_date' => now(),
        ]);

        // Можно добавить ещё ставок и значений
    }
}
