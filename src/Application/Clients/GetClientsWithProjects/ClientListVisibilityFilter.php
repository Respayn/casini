<?php

namespace Src\Application\Clients\GetClientsWithProjects;

use App\Models\User;
use App\Support\ClientsAndProjectsPermissions;

class ClientListVisibilityFilter
{
    /**
     * @param  ClientDto[]  $clients
     * @return ClientDto[]
     */
    public function filterForUser(array $clients, User $user): array
    {
        if (ClientsAndProjectsPermissions::userCanSeeAll($user)) {
            return $clients;
        }

        if (! ClientsAndProjectsPermissions::userCanSeeSelf($user)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (ClientDto $client) => $this->filterClientForSelfAccess($client, $user->id),
            $clients
        ), fn (ClientDto $client) => $client->projects !== [] || $client->managerId === $user->id));
    }

    private function filterClientForSelfAccess(ClientDto $client, int $userId): ClientDto
    {
        if ($client->managerId === $userId) {
            return $client;
        }

        $projects = array_values(array_filter(
            $client->projects,
            fn (ClientProjectDto $project) => $project->specialistId === $userId
        ));

        return new ClientDto(
            $client->id,
            $client->name,
            $client->inn,
            $client->initialBalance,
            $client->managerId,
            $projects
        );
    }
}
