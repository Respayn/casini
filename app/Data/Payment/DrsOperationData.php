<?php

namespace App\Data\Payment;

use Spatie\LaravelData\Data;

/**
 * Строка отчета ДРС.
 */
class DrsOperationData extends Data
{
    public function __construct(
        public int $id,
        public bool $isManual,
        public string $number,
        public string $operationDate,
        public ?string $sentDate,
        public float $bankAmount,
        public float $creditAmount,
        public ?string $paymentDetails,
        public ?string $advertisingSystem,
        public ?string $invoiceNumber,
        public ?string $managerName,
        public int $clientId,
        public string $clientName,
        public ?string $projectName,
        public float $adCabinetAmount,
        public float $feeAmount,
        public bool $isSentToCabinet,
        public ?string $statusChangedLabel,
        public bool $isFeeInPiggyBank,
        public bool $isInvoiceIssued,
        public ?string $invoiceChangedLabel,
        public bool $isNew,
        public ?string $comment,
    ) {}
}
