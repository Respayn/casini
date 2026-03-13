<?php

namespace Src\Application\Clients\Update;

use Src\Domain\Clients\Client;
use Src\Domain\Clients\ClientRepositoryInterface;

class UpdateClientCommandHandler
{
    private readonly ClientRepositoryInterface $clientRepository;

    public function __construct(ClientRepositoryInterface $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    public function handle(UpdateClientCommand $command): int
    {
        $client = $this->clientRepository->findById($command->id);

        $client->update(
            $command->name,
            $command->managerId,
            $command->inn,
            $command->initialBalance
        );

        return $this->clientRepository->save($client);
    }
}
