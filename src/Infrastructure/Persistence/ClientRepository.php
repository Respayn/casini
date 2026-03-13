<?php

namespace Src\Infrastructure\Persistence;

use App\Models\Client as EloquentClient;
use Src\Domain\Clients\Client;
use Src\Domain\Clients\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function findById(int $id): Client
    {
        $client = EloquentClient::find($id);
        return $this->mapToEntity($client);
    }

    public function save(Client $client): int
    {
        $clientId = $client->getId();

        $attributes = [
            'name' => $client->getName(),
            'manager_id' => $client->getManagerId(),
            'inn' => $client->getInn(),
            'initial_balance' => $client->getInitialBalance()
        ];

        if ($clientId === null) {
            $eloquentClient = new EloquentClient();
        } else {
            $eloquentClient = EloquentClient::findOrFail($clientId);
        }

        $eloquentClient->fill($attributes);
        $eloquentClient->save();
        
        return $eloquentClient->id;
    }

    private function mapToEntity(EloquentClient $client): Client
    {
        return Client::restore(
            $client->id,
            $client->name,
            $client->manager_id,
            $client->inn,
            $client->initial_balance
        );
    }
}
