<?php

namespace Src\Application\Clients;

use Src\Application\Clients\GetClientsWithProjects\ClientDto;

interface ClientReadRepositoryInterface
{
    /**
     * @return ClientDto[]
     */
    public function getClientsWithProjects(): array;
}