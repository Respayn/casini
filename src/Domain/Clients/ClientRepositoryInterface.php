<?php

namespace Src\Domain\Clients;

interface ClientRepositoryInterface
{
    public function findById(int $id): Client;
    public function findAll(): array;
    public function save(Client $client): int;
}