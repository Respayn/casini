<?php

namespace App\Services;

use App\Data\ClientData;
use App\Repositories\ClientRepository;
use Illuminate\Support\Collection;

class ClientService
{
    public function __construct(
        public ClientRepository $repository
    ) {}

    /**
     * @return Collection<ClientData>
     */
    public function getClients(array $with = []): Collection
    {
        return collect($this->repository->all($with));
    }

    public function hasProjects(int $clientId): bool
    {
        return $this->repository->hasProjects($clientId);
    }

    public function hasPayments(int $clientId): bool
    {
        return $this->repository->hasPayments($clientId);
    }

    public function hasReports(int $clientId): bool
    {
        return $this->repository->hasReports($clientId);
    }

    public function delete(int $clientId): void
    {
        $this->repository->deleteById($clientId);
    }
}
