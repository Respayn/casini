<?php

namespace Src\Application\Clients\Create;

use Src\Domain\Clients\Client;
use Src\Domain\Clients\ClientRepositoryInterface;

class CreateClientCommandHandler
{
    private readonly ClientRepositoryInterface $clientRepository;

    public function __construct(ClientRepositoryInterface $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    public function handle(CreateClientCommand $command): int
    {
        $client = Client::create(
            $command->name,
            $command->managerId,
            $command->inn,
            $command->initialBalance
        );

        return $this->clientRepository->save($client);
    }
}
