<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Services\Agency\AgencyIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agency>
 */
class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'time_zone' => $this->faker->timezone(),
            'direct_budget_refresh_time' => '09:00:00',
            'url' => $this->faker->url(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'logo_src' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Agency $agency) {
            if ($agency->id === null) {
                $agency->id = app(AgencyIdGenerator::class)->generate();
            }
        });
    }
}
