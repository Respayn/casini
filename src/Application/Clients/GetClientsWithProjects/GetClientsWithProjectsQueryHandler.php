<?php

namespace Src\Application\Clients\GetClientsWithProjects;

use Src\Application\Clients\ClientReadRepositoryInterface;

class GetClientsWithProjectsQueryHandler
{
    private readonly ClientReadRepositoryInterface $clientRepository;

    public function __construct(ClientReadRepositoryInterface $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * @return ClientDto[]
     */
    public function handle(GetClientsWithProjectsQuery $query): array
    {
        return $this->clientRepository->getClientsWithProjects();
    }
}