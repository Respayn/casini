<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface IntegrationRepositoryInterface extends RepositoryInterface
{
    public function getAllIntegrationsSettingsForProject(int $projectId): Collection;
    public function saveIntegrationSettings(array $attributes);
    public function removeIntegrationSettingsForProject(int $projectId);
}
