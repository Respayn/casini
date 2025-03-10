<?php

namespace App\Services;

use App\Repositories\ClientRepository;

class ClientService
{
    public function __construct(
      public ClientRepository $repository
    ) {
    }

    public function getClients(): \Illuminate\Support\Collection
    {
        return collect($this->repository->all());
    }
}
