<?php

namespace App\Services;

use App\Data\ClientData;
use App\Models\Client;
use App\Repositories\ClientRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Events\Notifications\ClientsDirectoryChanged;

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

            // Подсветим какие поля реально изменились
            $client->fill($data->toArray());
            $changedFields = array_values(array_diff(
                array_keys($client->getDirty()),
                ['id','created_at','updated_at','deleted_at']
            ));

            $client->save();

            event(new ClientsDirectoryChanged(
                userId:       Auth::id() ?? 0,
                clientId:     $client->id,
                clientName:   $client->name,
                projectId:    optional($client->projects->first())->id,
                projectName:  optional($client->projects->first())->name,
                changedFields:$changedFields
            ));
        } else {
            // create() сразу сохраняет запись
            $client = Client::query()->create($data->toArray());
            $client->save();

            event(new ClientsDirectoryChanged(
                userId:       Auth::id() ?? 0,
                clientId:     $client->id,
                clientName:   $client->name,
                projectId:    optional($client->projects->first())->id,
                projectName:  optional($client->projects->first())->name,
                changedFields:['created']
            ));
        }

        return ClientData::from($client);
    }
}
