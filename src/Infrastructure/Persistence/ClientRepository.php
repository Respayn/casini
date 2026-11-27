<?php

namespace Src\Infrastructure\Persistence;

use App\Enums\FeeType;
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
            'initial_balance' => $client->getInitialBalance(),
            'ad_fee_type' => $client->chargesAdFee() ? FeeType::THREE_PERCENT : FeeType::NONE,
            'ad_fee_changed_at' => $client->getAdFeeChangedAt()?->format('Y-m-d'),
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

    public function findAll(): array
    {
        $eloquentClients = EloquentClient::all();
        return $eloquentClients
            ->map(fn(EloquentClient $client) => $this->mapToEntity($client))
            ->toArray();
    }

    private function mapToEntity(EloquentClient $client): Client
    {
        return Client::restore(
            $client->id,
            $client->name,
            $client->manager_id,
            $client->inn,
            $client->initial_balance,
            $client->ad_fee_type !== FeeType::NONE,
            $client->ad_fee_changed_at?->toDateTimeImmutable()
        );
    }
}
