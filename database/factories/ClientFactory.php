<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'inn' => $this->faker->unique()->numerify('##########'),
            'initial_balance' => $this->faker->randomFloat(2, 0, 100000),
            'manager_id' => User::factory(),
        ];
    }
}
