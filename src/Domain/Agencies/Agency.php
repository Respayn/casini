<?php

namespace Src\Domain\Agencies;

class Agency
{
    public function __construct(
        private ?int $id,
        private string $address,
        private string $domain,
        private ?string $logoPath,
        private string $email,
        private string $phone
    ) {}

    public static function restore(
        int $id,
        string $address,
        string $domain,
        ?string $logoPath,
        string $email,
        string $phone
    ): Agency {
        return new self(
            $id,
            $address,
            $domain,
            $logoPath,
            $email,
            $phone
        );
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }
}
