<?php

namespace Src\Application\Clients\GetClientsWithProjects;

readonly class ClientProjectDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $projectType
    ) {}
}
