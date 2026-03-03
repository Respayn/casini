<?php

namespace Src\Domain\Clients;

interface ClientRepositoryInterface
{
    public function findById(int $id): Client;
}