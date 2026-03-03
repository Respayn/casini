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

    private function mapToEntity(EloquentClient $client): Client
    {
        return Client::restore(
            $client->id,
            $client->name,
            $client->manager_id
        );
    }
}
