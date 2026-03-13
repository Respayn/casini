<?php

namespace Src\Application\Clients\GetClientsWithProjects;

readonly class ClientDto
{
    /**
     * @param ClientProjectDto[] $projects
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $inn,
        public float $initialBalance,
        public int $managerId,
        public array $projects
    ) {}
}
