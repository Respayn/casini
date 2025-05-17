<?php

namespace App\Repositories\Interfaces;

use App\Data\Payment\PaymentData;
use App\Models\Payment;
use Spatie\LaravelData\DataCollection;

interface PaymentRepositoryInterface
{
    public function upsertPayment(PaymentData $data, int $clientId): Payment;
    public function syncInvoices(Payment $payment, DataCollection $invoices): void;
    public function findByNumber(string $number): ?Payment;
    public function deleteWithOperations(Payment $payment): void;
}
