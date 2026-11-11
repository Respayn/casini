<?php

namespace App\Repositories;

use App\Data\ClientData;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Report;
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

    public function hasProjects(int $clientId): bool
    {
        return Project::query()->where('client_id', $clientId)->exists();
    }

    public function hasPayments(int $clientId): bool
    {
        return Payment::withTrashed()->where('client_id', $clientId)->exists();
    }

    public function hasReports(int $clientId): bool
    {
        return Report::query()->where('client_id', $clientId)->exists();
    }

    public function deleteById(int $clientId): void
    {
        Client::query()->findOrFail($clientId)->delete();
    }
}
