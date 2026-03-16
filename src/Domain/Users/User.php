<?php

namespace Src\Domain\Users;

class User
{
    public function __construct(
        private ?int $id,
        private string $firstName,
        private string $lastName,
        private string $email,
        private string $phone,
        private ?string $imagePath,
        private array $agenciesIds
    ) {}

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function getAgencysIds(): array
    {
        return $this->agenciesIds;
    }
}
