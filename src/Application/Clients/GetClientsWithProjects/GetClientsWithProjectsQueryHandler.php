<?php

namespace Src\Application\Clients\GetClientsWithProjects;

use App\Models\User;
use Src\Application\Clients\ClientReadRepositoryInterface;

class GetClientsWithProjectsQueryHandler
{
    public function __construct(
        private readonly ClientReadRepositoryInterface $clientRepository,
        private readonly ClientListVisibilityFilter $visibilityFilter,
    ) {}

    /**
     * @return ClientDto[]
     */
    public function handle(GetClientsWithProjectsQuery $query): array
    {
        $clients = $this->clientRepository->getClientsWithProjects();
        $user = User::query()->findOrFail($query->viewerUserId);

        $clients = $this->visibilityFilter->filterForUser($clients, $user);

        if ($query->projectId === null) {
            return $clients;
        }

        $projectId = $query->projectId;
        $filtered = [];

        foreach ($clients as $client) {
            $projects = array_values(array_filter(
                $client->projects,
                fn (ClientProjectDto $project) => $project->id === $projectId
            ));

            if ($projects === []) {
                continue;
            }

            $filtered[] = new ClientDto(
                $client->id,
                $client->name,
                $client->inn,
                $client->initialBalance,
                $client->managerId,
                $projects
            );
        }

        return $filtered;
    }
}
