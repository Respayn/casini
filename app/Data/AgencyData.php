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
        #[DataCollectionOf(AgencyUserData::class)]
        public DataCollection $users,
        public string $timeZone,
        public string $directBudgetRefreshTime = '09:00',
        public ?string $url = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $logoSrc = null,
    ) {}
}
