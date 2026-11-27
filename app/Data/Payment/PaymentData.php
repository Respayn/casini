<?php

namespace App\Data\Payment;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class PaymentData extends Data
{
    public function __construct(
        #[Rule('required|string|max:50')]
        public string $PaymentNumber,

        #[Rule('required|date')]
        public \DateTimeImmutable $PaymentDate,

        #[Rule('required|string|max:255')]
        public string $Payer,

        #[Rule(['required', 'regex:/^(\d{10}|\d{12})$/'])]
        public string $InnPayer,

        #[Rule('nullable|string|max:100')]
        public ?string $ContractNumber,

        #[Rule('required|numeric|min:0')]
        public float $Total,

        #[Rule('required|boolean')]
        public bool $Canceled,

        #[Rule('nullable|string')]
        public ?string $Purpose,

        /** @var InvoiceData[] */
        #[Rule('required_unless:Canceled,true|array|min:1')]
        public ?DataCollection $invoices = null
    ) {}

    public static function collection(array $data): DataCollection
    {
        return new DataCollection(static::class, $data);
    }
}
