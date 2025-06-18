<?php

namespace App\Services;

use App\Data\Payment\InvoiceData;
use App\Data\Payment\PaymentData;
use App\Models\Client;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Log;
use Spatie\LaravelData\DataCollection;

class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepo,
        private ConnectionInterface $db
    ) {}

    public function processPayments(iterable $payments): void
    {
        $this->db->transaction(function () use ($payments) {
            foreach ($payments as $paymentData) {
                try {
                    $this->processPayment($paymentData);
                } catch (\Exception $e) {
                    Log::error('Payment processing failed', [
                        'error' => $e->getMessage(),
                        'data' => $paymentData->toArray()
                    ]);
                }
            }
        });
    }

    private function processPayment(PaymentData $data): void
    {
        if ($data->Canceled) {
            $this->cancelPayment($data);
            return;
        }

        $client = $this->resolveClient($data);
        $payment = $this->paymentRepo->upsertPayment(
            $data->PaymentNumber,
            Carbon::parse($data->PaymentDate),
            $client->id
        );

        $this->paymentRepo->syncInvoices(
            $payment,
            $this->parseInvoices($data->invoices),
            $data->Purpose
        );
    }

    private function parseInvoices(DataCollection $invoices): DataCollection
    {
        return InvoiceData::collection(
            $invoices->toCollection()->map(function ($invoice) {
                return [
                    'invoice_number' => $invoice->invoiceNumber,
                    'invoice_date' => $invoice->invoiceDate,
                    'sum' => $invoice->sum
                ];
            })->all()
        );
    }

    private function resolveClient(PaymentData $data): Client
    {
        return Client::firstOrCreate(
            ['inn' => $data->InnPayer],
            ['name' => $data->Payer]
        );
    }

    private function cancelPayment(PaymentData $data): void
    {
        if ($payment = $this->paymentRepo->findByNumber($data->PaymentNumber)) {
            $this->paymentRepo->deleteWithOperations($payment);
        }
    }
}
