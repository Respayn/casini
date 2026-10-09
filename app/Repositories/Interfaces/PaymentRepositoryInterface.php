<?php

namespace App\Repositories\Interfaces;

use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentOperation;
use App\Models\Project;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

interface PaymentRepositoryInterface
{
    public function upsertPayment(string $paymentNumber, \DateTimeInterface $paymentDate, int $clientId): Payment;

    public function syncInvoices(Payment $payment, DataCollection $invoices, string $purpose): void;

    public function findByNumber(string $number): ?Payment;

    public function deleteWithOperations(Payment $payment): void;

    /**
     * @param  int|null  $visibleForUserId  null - все клиенты агентства
     * @return Collection<int, PaymentOperation>
     */
    public function getOperationsForPeriod(
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $visibleForUserId,
        ?int $projectId,
        bool $onlyNew
    ): Collection;

    public function findOperation(int $operationId, ?int $visibleForUserId): ?PaymentOperation;

    /**
     * @return Collection<int, PaymentOperation>
     */
    public function getClientOperations(int $clientId, ?int $exceptOperationId = null): Collection;

    public function updateOperation(PaymentOperation $operation, array $attributes): void;

    public function hideOperation(PaymentOperation $operation): void;

    public function createManualOperation(int $clientId, \DateTimeInterface $date, array $attributes): PaymentOperation;

    /**
     * @return Collection<int, PaymentOperation>
     */
    public function getUnprocessedOperations(CarbonInterface $receivedBefore): Collection;

    /**
     * @return Collection<int, PaymentOperation>
     */
    public function getPaymentOperations(Payment $payment): Collection;

    public function updateManualPayment(Payment $payment, array $attributes): void;

    public function findClient(int $clientId): ?Client;

    /**
     * @return Collection<int, Project>
     */
    public function getClientProjects(int $clientId): Collection;

    public function findClientProjectWithIntegration(int $clientId, string $integrationCode): ?Project;

    /**
     * @param  int|null  $visibleForUserId  null - все клиенты агентства
     * @return Collection<int, Client>
     */
    public function getVisibleClients(?int $visibleForUserId): Collection;

    public function getLastImportAt(): ?CarbonInterface;
}
