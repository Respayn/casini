<?php

namespace App\Repositories;

use App\Enums\PaymentSource;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentOperation;
use App\Models\Project;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function upsertPayment(string $paymentNumber, \DateTimeInterface $paymentDate, int $clientId): Payment
    {
        return Payment::updateOrCreate(
            ['number' => $paymentNumber, 'source' => PaymentSource::FROM_1C],
            [
                'received_date' => $paymentDate,
                'client_id' => $clientId,
            ]
        );
    }

    public function syncInvoices(Payment $payment, DataCollection $invoices, string $purpose): void
    {
        $existing = $payment->operations()->withTrashed()->get()->keyBy('order');
        $orders = [];

        foreach ($invoices as $index => $invoice) {
            $order = $index + 1;
            $orders[] = $order;
            $attributes = [
                'invoice_number' => $invoice->invoiceNumber,
                'invoice_date' => $invoice->invoiceDate,
                'bank_received_amount' => $invoice->sum,
                'payment_details' => $purpose,
            ];

            $operation = $existing->get($order);

            if ($operation === null) {
                PaymentOperation::create($attributes + [
                    'payment_id' => $payment->id,
                    'order' => $order,
                    'cabinet_top_up_amount' => $invoice->sum,
                    'ad_cabinet_amount' => $invoice->sum,
                    'manager_id' => $payment->client?->manager_id,
                ]);

                continue;
            }

            $operation->update($attributes);
        }

        $payment->operations()->whereNotIn('order', $orders)->delete();
    }

    public function findByNumber(string $number): ?Payment
    {
        return Payment::where('number', $number)
            ->where('source', PaymentSource::FROM_1C)
            ->first();
    }

    public function deleteWithOperations(Payment $payment): void
    {
        $payment->operations()->delete();
        $payment->delete();
    }

    public function getOperationsForPeriod(
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $visibleForUserId,
        ?int $projectId,
        bool $onlyNew
    ): Collection {
        return PaymentOperation::query()
            ->with(['payment.client', 'project', 'manager', 'statusChangedBy', 'invoiceChangedBy'])
            ->whereHas('payment', function (Builder $query) use ($from, $to, $visibleForUserId, $projectId) {
                $query->whereBetween('received_date', [$from->toDateString(), $to->toDateString()]);

                if ($visibleForUserId !== null) {
                    $query->whereHas('client', fn (Builder $client) => $this->whereClientVisibleFor($client, $visibleForUserId));
                }

                if ($projectId !== null) {
                    $query->whereHas('client.projects', fn (Builder $project) => $project->whereKey($projectId));
                }
            })
            ->when($projectId !== null, fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner->where('project_id', $projectId)->orWhereNull('project_id')
            ))
            ->when($onlyNew, fn (Builder $query) => $query->whereNull('opened_at'))
            ->get()
            ->sortByDesc(fn (PaymentOperation $operation) => [
                $operation->payment->received_date->toDateString(),
                $operation->id,
            ])
            ->values();
    }

    public function findOperation(int $operationId, ?int $visibleForUserId): ?PaymentOperation
    {
        return PaymentOperation::query()
            ->with(['payment.client', 'statusChangedBy', 'invoiceChangedBy'])
            ->when($visibleForUserId !== null, fn (Builder $query) => $query->whereHas(
                'payment.client',
                fn (Builder $client) => $this->whereClientVisibleFor($client, $visibleForUserId)
            ))
            ->find($operationId);
    }

    /**
     * Операции клиента, по которым считаются долги (скрытые не учитываются).
     */
    public function getClientOperations(int $clientId, ?int $exceptOperationId = null): Collection
    {
        return PaymentOperation::query()
            ->whereHas('payment', fn (Builder $query) => $query->where('client_id', $clientId))
            ->when($exceptOperationId !== null, fn (Builder $query) => $query->whereKeyNot($exceptOperationId))
            ->get();
    }

    public function updateOperation(PaymentOperation $operation, array $attributes): void
    {
        $operation->update($attributes);
    }

    public function hideOperation(PaymentOperation $operation): void
    {
        $operation->delete();
    }

    public function createManualOperation(int $clientId, \DateTimeInterface $date, array $attributes): PaymentOperation
    {
        $payment = Payment::create([
            'number' => (string) $this->nextManualNumber(),
            'source' => PaymentSource::MANUAL,
            'received_date' => $date,
            'client_id' => $clientId,
        ]);

        return PaymentOperation::create($attributes + [
            'payment_id' => $payment->id,
            'order' => 1,
            'bank_received_amount' => 0,
            'payment_details' => PaymentSource::MANUAL->label(),
        ]);
    }

    /**
     * Поступления из 1С, которые никто не открыл и не отправил в кабинет.
     */
    public function getUnprocessedOperations(CarbonInterface $receivedBefore): Collection
    {
        return PaymentOperation::query()
            ->with(['payment.client.projects', 'manager'])
            ->whereNull('opened_at')
            ->whereNull('unprocessed_notified_at')
            ->where('is_sent_to_cabinet', false)
            ->whereHas('payment', fn (Builder $query) => $query
                ->where('source', PaymentSource::FROM_1C)
                ->where('received_date', '<=', $receivedBefore->toDateString()))
            ->get();
    }

    public function getPaymentOperations(Payment $payment): Collection
    {
        return $payment->operations()->get();
    }

    public function updateManualPayment(Payment $payment, array $attributes): void
    {
        $payment->update($attributes);
    }

    public function findClient(int $clientId): ?Client
    {
        return Client::query()->find($clientId);
    }

    public function getClientProjects(int $clientId): Collection
    {
        return Project::query()
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get();
    }

    public function findClientProjectWithIntegration(int $clientId, string $integrationCode): ?Project
    {
        return Project::query()
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->whereHas('integrations', fn (Builder $query) => $query
                ->where('code', $integrationCode)
                ->where('integration_project.is_enabled', true))
            ->orderBy('id')
            ->first();
    }

    public function getVisibleClients(?int $visibleForUserId): Collection
    {
        return Client::query()
            ->when($visibleForUserId !== null, fn (Builder $query) => $this->whereClientVisibleFor($query, $visibleForUserId))
            ->orderBy('name')
            ->get();
    }

    public function getLastImportAt(): ?CarbonInterface
    {
        $value = Payment::withTrashed()->where('source', PaymentSource::FROM_1C)->max('updated_at');

        return $value === null ? null : Carbon::parse($value);
    }

    private function nextManualNumber(): int
    {
        $numbers = Payment::withTrashed()
            ->where('source', PaymentSource::MANUAL)
            ->pluck('number');

        return (int) $numbers->map(fn (string $number) => (int) $number)->max() + 1;
    }

    /**
     * Свои клиенты: менеджер клиента или специалист его проекта. Клиент без менеджера (неузнанный ИНН) виден всем.
     */
    private function whereClientVisibleFor(Builder $client, int $userId): void
    {
        $client->where(fn (Builder $query) => $query
            ->whereNull('manager_id')
            ->orWhere('manager_id', $userId)
            ->orWhereHas('projects', fn (Builder $project) => $project->where('specialist_id', $userId)));
    }
}
