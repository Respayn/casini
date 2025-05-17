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
    public function upsertPayment(PaymentData $data, int $clientId): Payment
    {
        return Payment::updateOrCreate(
            [
                'number' => $data->PaymentNumber, // Исправлено
                'source' => PaymentSource::FROM_1C
            ],
            [
                'received_date' => $data->PaymentDate, // Исправлено
                'client_id' => $clientId,
                'external_id' => $data->PaymentNumber // Исправлено
            ]
        );
    }

    public function syncInvoices(Payment $payment, DataCollection $invoices): void
    {
        $payment->operations()->delete();
        foreach ($invoices as $index => $invoice) {
            PaymentOperation::create([
                'payment_id' => $payment->id,
                'order' => $index + 1,
                'invoice_number' => $invoice->InvoiceNumber, // Исправлено
                'invoice_date' => $invoice->InvoiceDate, // Исправлено
                'bank_received_amount' => $invoice->Sum, // Исправлено
                'cabinet_top_up_amount' => $invoice->Sum, // Исправлено
                'payment_details' => $payment->purpose
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
