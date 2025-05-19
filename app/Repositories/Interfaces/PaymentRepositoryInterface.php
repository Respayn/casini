<?php

namespace App\Repositories\Interfaces;

use App\Data\Payment\PaymentData;
use App\Models\Payment;
use Spatie\LaravelData\DataCollection;

interface PaymentRepositoryInterface
{
    public function upsertPayment(string $paymentNumber, \DateTimeInterface $paymentDate, int $clientId): Payment;
    public function syncInvoices(Payment $payment, DataCollection $invoices, string $purpose): void;
    public function findByNumber(string $number): ?Payment;
    public function deleteWithOperations(Payment $payment): void;
}
