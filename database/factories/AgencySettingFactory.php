<?php

namespace Database\Factories;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agency>
 */
class AgencySettingFactory extends Factory
{
    protected $model = Agency::class;

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
