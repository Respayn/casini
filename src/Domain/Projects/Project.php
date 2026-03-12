<?php

namespace Src\Domain\Projects;

use Src\Domain\ValueObjects\ProjectType;

class Project
{
    public function __construct(
        private int $id,
        private string $name,
        private int $clientId,
        private ProjectType $type,
        private int $specialistId,
        private string $domain
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getType(): ProjectType
    {
        return $this->type;
    }

    public function getSpecialistId(): int
    {
        return $this->specialistId;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
