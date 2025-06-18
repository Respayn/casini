<?php

namespace App\Data\Accounting;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class WorkActData extends Data
{
    public function __construct(
        #[Required, Rule('string|max:50')]
        public string $actNumber,

        #[Required, Date]
        public \DateTimeImmutable $actDate,

        #[Required]
        public CompanyData $company,

        #[Required, Numeric]
        public float $total,

        /** @var WorkActItemData[] */
        #[Required, ArrayType]
        public DataCollection $items
    ) {}

    public static function collection(array $data): DataCollection
    {
        return new DataCollection(static::class, $data);
    }
}

