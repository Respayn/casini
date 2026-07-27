<?php

namespace Src\Application\Clients\GetClientsWithProjects;

use App\Enums\PermissionGroup;
use App\Models\User;

class ClientListVisibilityFilter
{
    /**
     * @param  ClientDto[]  $clients
     * @return ClientDto[]
     */
    public function filterForUser(array $clients, User $user): array
    {
        if ($this->userCanSeeAll($user)) {
            return $clients;
        }

        if (! $this->userCanSeeSelf($user)) {
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

    private function userCanSeeAll(User $user): bool
    {
        return $user->hasAnyPermission($this->permissionNames(PermissionGroup::CLIENTS_AND_PROJECTS_ALL));
    }

    private function userCanSeeSelf(User $user): bool
    {
        return $user->hasAnyPermission($this->permissionNames(PermissionGroup::CLIENTS_AND_PROJECTS_SELF));
    }

    /**
     * @return list<string>
     */
    private function permissionNames(PermissionGroup $group): array
    {
        $name = $group->value;

        return [
            'read '.$name,
            'edit '.$name,
            'full '.$name,
        ];
    }
}
