<?php

namespace App\Data\Accounting;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CompanyData extends Data
{
    public function __construct(
        #[Rule('required|digits:10')]
        public string $inn,

        #[Rule('required|string|max:100')]
        public string $contractNumber,

        #[Rule('nullable|string|max:100')]
        public ?string $additionalNumbers
    ) {}
}
