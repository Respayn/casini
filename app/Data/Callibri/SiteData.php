<?php

namespace App\Data\Callibri;

use App\Data\Casts\StringToBoolCast;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

class SiteData extends Data
{
    public function __construct(
        #[MapInputName('site_id')]
        public int $id,

        #[MapInputName('sitename')]
        public string $name,

        #[MapInputName('domains')]
        public string $domains,

        #[WithCast(StringToBoolCast::class)]
        #[MapInputName('active')]
        public bool $isActive,

        #[MapInputName('created_at')]
        public ?Carbon $created_at = null
    ) {
    }
}
