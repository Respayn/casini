<?php

namespace Database\Factories;

use App\Enums\ProjectType;
use App\Enums\ServiceType;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'domain' => $this->faker->word(),
            'project_type' => $this->faker->randomElement(ProjectType::cases()),
            'service_type' => $this->faker->randomElement(ServiceType::cases()),
            'kpi' => $this->faker->word(),
            'is_internal' => $this->faker->boolean(),
            'is_active' => $this->faker->boolean(),
            'traffic_attribution' => $this->faker->word(),
            'metrika_counter' => $this->faker->word(),
            'metrika_targets' => $this->faker->word(),
            'google_ads_client_id' => $this->faker->word(),
            'contract_number' => $this->faker->word(),
            'additional_contract_number' => $this->faker->word(),
            'recommendation_url' => $this->faker->url(),
            'legal_entity' => $this->faker->word(),
            'inn' => $this->faker->word(),
            'created_at' => $this->faker->dateTime(),
            'updated_at' => $this->faker->dateTime(),

            'client_id' => Client::factory(),
            'specialist_id' => User::factory(),
        ];
    }
}
