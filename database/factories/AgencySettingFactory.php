<?php

namespace Database\Factories;

use App\Models\AgencySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgencySetting>
 */
class AgencySettingFactory extends Factory
{
    protected $model = AgencySetting::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'time_zone' => $this->faker->timezone,
            'url' => $this->faker->url,
            'email' => $this->faker->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'logo_src' => null,
        ];
    }
}
