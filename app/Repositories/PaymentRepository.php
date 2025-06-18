<?php

namespace App\Repositories;

use App\Data\Payment\PaymentData;
use App\Enums\PaymentSource;
use App\Models\Payment;
use App\Models\PaymentOperation;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Spatie\LaravelData\DataCollection;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function upsertPayment(string $paymentNumber, \DateTimeInterface $paymentDate, int $clientId): Payment
    {
        return Payment::updateOrCreate(
            ['number' => $paymentNumber],
            [
                'received_date' => $paymentDate,
                'client_id' => $clientId,
                'source' => PaymentSource::FROM_1C
            ]
        );
    }

    public function syncInvoices(Payment $payment, DataCollection $invoices, string $purpose): void
    {
        $payment->operations()->delete();
        foreach ($invoices as $index => $invoice) {
            PaymentOperation::create([
                'payment_id' => $payment->id,
                'order' => $index + 1,
                'invoice_number' => $invoice->invoiceNumber,
                'invoice_date' => $invoice->invoiceDate,
                'bank_received_amount' => $invoice->sum,
                'cabinet_top_up_amount' => $invoice->sum,
                'payment_details' => $purpose
            ]);
        }
    }

    public function findByNumber(string $number): ?Payment
    {
        return Payment::where('number', $number)->first();
    }

    public function deleteWithOperations(Payment $payment): void
    {
        $payment->operations()->delete();
        $payment->delete();
    }
}
