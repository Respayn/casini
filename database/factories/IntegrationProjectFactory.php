<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntegrationProject>
 */
class IntegrationProjectFactory extends Factory
{
    protected $model = IntegrationProject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::first()->id,
            'integration_id' => Integration::inRandomOrder()->first()->id,
            'is_enabled' => true,
            'settings' => [
                'email' => config('services.callibri.test_email'),
                'token' => config('services.callibri.test_token'),
                'site_id' => config('services.callibri.test_site_id'),
                'utm_source' => 'test_source',
                'utm_campaign' => 'test_campaign',
                'appeals_type' => ['type1', 'type2'],
                'appeals_filter' => 'first_only',
                'lead_cost_calc' => 'selected_classes_only',
                'appeals_class' => 'class1,class2'
            ]
        ];
    }

    public function forCallibri(): self
    {
        return $this->state([
            'integration_id' => Integration::where('code', 'callibri')->firstOrFail()->id
        ]);
    }

    public function disabled(): self
    {
        return $this->state(['is_enabled' => false]);
    }

    public function withSettings(array $settings): self
    {
        return $this->state(['settings' => array_merge(
            $this->definition()['settings'],
            $settings
        )]);
    }

    public function withProject(Project $project): self
    {
        return $this->state(['project_id' => $project->id]);
    }
}
