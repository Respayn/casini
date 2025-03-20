<?php

namespace App\Services;

use App\Repositories\IntegrationRepository;
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

    public function updateNotification(int $integrationId, string $notification)
    {
        $integration = $this->repository->find($integrationId);
        $integration->notification = trim($notification);
        $this->repository->save($integration->toArray());
    }
}
