<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class AgencyData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        #[DataCollectionOf(AgencyAdminData::class)]
        public DataCollection $admins,
        public string $timeZone,
        public ?string $url,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $logoSrc,
    ) {}
}
