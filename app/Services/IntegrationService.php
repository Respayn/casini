<?php

namespace App\Services;

use App\Data\ProjectForm\ProjectIntegrationData;
use App\Repositories\IntegrationRepository;
use App\Services\IntegrationSync\IntegrationSyncIssueStatus;
use Illuminate\Support\Collection;

class IntegrationService
{
    private readonly IntegrationRepository $repository;

    public function __construct(IntegrationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getIntegrations(): Collection
    {
        return collect($this->repository->all());
    }

    /**
     * @return Collection<ProjectIntegrationData>
     */
    public function getIntegrationSettingsForProject(int $projectId): Collection
    {
        $integrationSettings = $this->repository->getAllIntegrationsSettingsForProject($projectId);

        return collect($integrationSettings)->keyBy('integration.id');
    }

    public function updateNotification(int $integrationId, string $notification)
    {
        $integration = $this->repository->find($integrationId);
        $integration->notification = trim($notification);
        $this->repository->save($integration->toArray());
    }

    /**
     * @param  Collection<ProjectIntegrationData>  $integrationsSettings
     */
    public function updateIntegrationsSettings(int $projectId, Collection $integrationsSettings)
    {
        $previousByIntegrationId = $this->repository->projectIntegrationSettingsByIntegrationId($projectId);

        $this->repository->removeIntegrationSettingsForProject($projectId);

        $integrationsSettings->each(function ($settings, $integrationId) use ($projectId, $previousByIntegrationId) {
            $settingsCollection = collect($settings);
            $incoming = collect($settingsCollection->pull('settings'))->toArray();
            $previous = $previousByIntegrationId[(int) $integrationId] ?? [];

            $attributes = [
                'project_id' => $projectId,
                'integration_id' => $integrationId,
                'is_enabled' => $settingsCollection->pull('isEnabled'),
                'settings' => IntegrationSyncIssueStatus::mergePreserved($previous, $incoming),
            ];
            $this->repository->saveIntegrationSettings($attributes);
        });
    }

    /**
     * @param  ProjectIntegrationData  $settings
     * @return void
     */
    public function saveIntegrationSettings(int $projectId, ProjectIntegrationData $data)
    {
        $settingsCollection = collect($data);
        $attributes = [
            'project_id' => $projectId,
            'integration_id' => $data->integration->id,
            'is_enabled' => $settingsCollection->pull('isEnabled'),
            'settings' => $settingsCollection->pull('settings'),
        ];
        $this->repository->saveIntegrationSettings($attributes);
    }
}
