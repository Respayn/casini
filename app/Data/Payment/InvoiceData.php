<?php

namespace App\Data\Payment;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

class InvoiceData extends Data
{
    public function __construct(
        #[Rule('nullable|string|max:50')]
        public ?string $invoiceNumber,

        #[Rule('required|date')]
        public \DateTimeImmutable $invoiceDate,

        #[Rule('required|numeric|min:0')]
        public float $sum
    ) {}
}
