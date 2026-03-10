<?php

namespace Src\Domain\Agencies;

class Agency
{
    public function __construct(
        private ?int $id,
        private string $address,
        private string $domain,
        private string $logoPath,
        private string $email,
        private string $phone
    ) {}

    public static function restore(array $data): Agency
    {
        return new self(
            $data['id'],
            $data['address'],
            $data['url'],
            $data['logo_src'],
            $data['email'],
            $data['phone']
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

    public function getLogoPath(): string
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
