<?php

namespace App\Repositories;

use App\Data\ClientData;
use App\Models\Client;
use App\Repositories\Interfaces\RepositoryInterface;

class ClientRepository extends EloquentRepository implements RepositoryInterface
{
    public function model()
    {
        return Client::class;
    }

    public function all(array $with = ['manager.roles'])
    {
        $clients = Client::with($with)->get();
        return ClientData::collect($clients);
    }

    public function find(int $id): ?ClientData
    {
        $client = Client::with('manager.roles')->find($id);

        if ($client) {
            return ClientData::from($client);
        }

        return null;
    }

    public function findBy(string $column, mixed $value)
    {
        return ClientData::from($this->model->where($column, $value)->get());

    }
}
