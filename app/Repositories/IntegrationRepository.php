<?php

namespace App\Repositories;

use App\Data\Integrations\IntegrationData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;
use Illuminate\Support\Collection;

class IntegrationRepository extends EloquentRepository implements IntegrationRepositoryInterface
{
    public function model()
    {
        return Integration::class;
    }

    public function all(array $with = [])
    {
        $query = $this->queryWith($with);
        return IntegrationData::collect($query->get());
    }

    public function find(int $id)
    {
        return IntegrationData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return IntegrationData::collect($this->model->where($column, $value)->get());
    }

    /**
     * @return Collection<ProjectIntegrationData>
     */
    public function getAllIntegrationsSettingsForProject(int $projectId): Collection
    {
        $integrationSettings = IntegrationProject::where('project_id', $projectId)
            ->with('integration')
            ->get()
            ->map(function ($setting) {
                $integrationData = new ProjectIntegrationData();
                $integrationData->integration = IntegrationData::from($setting->integration);
                $integrationData->settings = json_decode($setting->settings, true);
                $integrationData->isEnabled = $setting->is_enabled;
                return $integrationData;
            });

        return collect($integrationSettings);
    }

    public function saveIntegrationSettings(array $attributes)
    {
        IntegrationProject::updateOrCreate(
            [
                'integration_id' => $attributes['integration_id'],
                'project_id' => $attributes['project_id']
            ],
            [
                'is_enabled' => $attributes['is_enabled'],
                'settings' => json_encode($attributes['settings']) // Сериализация в JSON
            ]
        );
    }

    public function  removeIntegrationSettingsForProject(int $projectId)
    {
        IntegrationProject::where('project_id', $projectId)->delete();
    }

    public function getActiveCallibriIntegration(int $projectId): IntegrationProject
    {
        $integration = Integration::where('code', 'callibri')->first();

        $projectIntegration = IntegrationProject::where([
            'project_id' => $projectId,
            'integration_id' => $integration->id,
            'is_enabled' => true
        ])->first();

        if (!$projectIntegration) {
            throw new \RuntimeException(
                "Callibri integration not configured for project $projectId"
            );
        }

        return $projectIntegration;
    }
}
