<?php

namespace App\Services;

use App\Data\ClientData;
use App\Repositories\ClientRepository;
use Illuminate\Support\Collection;

class ClientService
{
    public function __construct(
      public ClientRepository $repository
    ) {
    }

    /**
     * @return Collection<\App\Data\ClientData>
     */
    public function getClients(array $with = []): Collection
    {
        return collect($this->repository->all($with));
    }
}
