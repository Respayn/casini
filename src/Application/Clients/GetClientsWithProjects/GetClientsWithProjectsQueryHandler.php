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

        return $this->visibilityFilter->filterForUser($clients, $user);
    }
}
