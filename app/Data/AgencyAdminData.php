<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AgencyAdminData extends Data
{
    public function __construct(
        public int $id,
    ) {}
}
