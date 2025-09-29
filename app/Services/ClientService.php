<?php

namespace App\Services;

use App\Data\ClientData;
use App\Models\Client;
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

    public function updateOrCreateClient(ClientData $data): ClientData
    {
        if ($data->id) {
            $client = Client::query()->with('projects')->findOrFail($data->id);
            $client->fill($data->toArray());
            $client->save();
        } else {
            $client = Client::query()->create($data->toArray());
            $client->save();
        }

        return ClientData::from($client);
    }

    public function getById(int $id): ClientData|null
    {
        return $this->repository->find($id);
    }
}
