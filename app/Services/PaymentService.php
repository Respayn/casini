<?php

namespace App\Services;

use App\Data\Payment\DrsOperationData;
use App\Data\Payment\DrsOperationFormData;
use App\Data\Payment\InvoiceData;
use App\Data\Payment\PaymentData;
use App\Dictionaries\TimeZoneDictionary;
use App\Enums\AdvertisingSystem;
use App\Enums\FeeType;
use App\Enums\PaymentSource;
use App\Enums\PermissionGroup;
use App\Events\Notifications\FundsIncomeUnprocessed;
use App\Events\Notifications\FundsReceived;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentOperation;
use App\Models\Project;
use App\Models\User;
use App\Repositories\AgencyRepository;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Support\ClientsAndProjectsPermissions;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Log;
use Spatie\LaravelData\DataCollection;

class PaymentService
{
    public const AD_BUDGET_FLOW_INTEGRATION = '1c_ad_budget_flow';

    public const UNPROCESSED_WORKING_DAYS = 3;

    private ?string $agencyTimezone = null;

    public function __construct(
        private PaymentRepositoryInterface $paymentRepo,
        private ConnectionInterface $db,
        private AgencyRepository $agencyRepository
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
                        'data' => $paymentData->toArray(),
                    ]);
                }
            }
        });
    }

    /**
     * @return Collection<int, DrsOperationData>
     */
    public function getMonthOperations(User $user, CarbonInterface $month, ?int $projectId, bool $onlyNew): Collection
    {
        return $this->paymentRepo
            ->getOperationsForPeriod(
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
                $this->visibleForUserId($user),
                $projectId,
                $onlyNew
            )
            ->map(fn (PaymentOperation $operation) => $this->toRowData($operation));
    }

    /**
     * Открывает операцию в окне: снимает метку «новая» и подставляет пополнение по умолчанию.
     */
    public function openOperation(User $user, int $operationId): ?DrsOperationFormData
    {
        $operation = $this->paymentRepo->findOperation($operationId, $this->visibleForUserId($user));

        if ($operation === null) {
            return null;
        }

        $isUntouched = $operation->opened_at === null;

        if ($isUntouched) {
            $this->paymentRepo->updateOperation($operation, ['opened_at' => now()]);
        }

        $client = $operation->payment->client;

        return DrsOperationFormData::from([
            'id' => $operation->id,
            'isManual' => $operation->payment->source === PaymentSource::MANUAL,
            'number' => $operation->payment->number,
            'operationDate' => $operation->payment->received_date->toDateString(),
            'sentDate' => $operation->ad_cabinet_sent_date?->toDateString(),
            'bankAmount' => $operation->bank_received_amount,
            'creditAmount' => $operation->credit_amount,
            'clientId' => $client->id,
            'projectId' => $operation->project_id,
            'managerId' => $operation->manager_id ?? $client->manager_id,
            'advertisingSystem' => $operation->advertising_system?->value,
            'feeIncluded' => $this->clientFeeType($client, $operation->payment->received_date) === FeeType::THREE_PERCENT,
            'topUpAmount' => $isUntouched
                ? round($operation->bank_received_amount - $operation->credit_amount, 2)
                : $operation->cabinet_top_up_amount,
            'includeFeeDebt' => $operation->included_fee_debt > 0,
            'includedFeeDebt' => $operation->included_fee_debt,
            'isSentToCabinet' => $operation->is_sent_to_cabinet,
            'isFeeInPiggyBank' => $operation->is_fee_in_piggy_bank,
            'isInvoiceIssued' => $operation->is_invoice_issued,
            'comment' => $operation->comment,
            'paymentDetails' => $operation->payment_details,
        ]);
    }

    public function newCreditForm(): DrsOperationFormData
    {
        return DrsOperationFormData::from([
            'isManual' => true,
            'operationDate' => $this->agencyToday()->toDateString(),
            'paymentDetails' => PaymentSource::MANUAL->label(),
        ]);
    }

    /**
     * Менеджер операции всегда берется из карточки клиента в «Клиенты и клиенто-проекты».
     */
    public function getClientManagerId(int $clientId): ?int
    {
        return $this->paymentRepo->findClient($clientId)?->manager_id;
    }

    /**
     * Сбор не выбирается в окне: он следует настройке клиента на дату операции.
     */
    public function isClientFeeIncluded(int $clientId, CarbonInterface|string|null $operationDate): bool
    {
        $client = $this->paymentRepo->findClient($clientId);

        return $client === null || $this->clientFeeType($client, $operationDate) === FeeType::THREE_PERCENT;
    }

    /**
     * «Не взимаем сбор» действует с даты изменения расчета; операции до нее остаются со сбором 3%.
     */
    private function clientFeeType(Client $client, CarbonInterface|string|null $operationDate): FeeType
    {
        if ($client->ad_fee_type !== FeeType::NONE) {
            return FeeType::THREE_PERCENT;
        }

        $date = $operationDate === null || $operationDate === '' ? $this->agencyToday() : Carbon::parse($operationDate);

        return $client->ad_fee_changed_at !== null && $date->lt($client->ad_fee_changed_at)
            ? FeeType::THREE_PERCENT
            : FeeType::NONE;
    }

    /**
     * Кредитная задолженность клиента: минус - клиент должен агентству.
     */
    public function getClientCreditDebt(int $clientId): float
    {
        return round((float) $this->paymentRepo->getClientOperations($clientId)->sum('credit_amount'), 2);
    }

    /**
     * Несобранный сбор 3%: операции, где сбор не удержали, минус уже вычтенный долг.
     */
    public function getClientFeeDebt(int $clientId, ?int $exceptOperationId = null): float
    {
        $client = $this->paymentRepo->findClient($clientId);

        if ($client === null || $client->ad_fee_type !== FeeType::THREE_PERCENT) {
            return 0.0;
        }

        $operations = $this->paymentRepo->getClientOperations($clientId, $exceptOperationId);

        if ($operations->isEmpty()) {
            return 0.0;
        }

        $missed = $operations
            ->reject(fn (PaymentOperation $operation) => $operation->fee_included)
            ->sum(fn (PaymentOperation $operation) => FeeType::THREE_PERCENT->feeFrom($operation->cabinet_top_up_amount));
        $included = $operations->sum('included_fee_debt');

        return round(max(0, $missed - $included), 2);
    }

    /**
     * @return array{fee: float, cabinet: float, includedFeeDebt: float}
     */
    public function calculate(DrsOperationFormData $form, float $feeDebt): array
    {
        $topUp = max(0, round($form->topUpAmount, 2));
        $fee = ($form->feeIncluded ? FeeType::THREE_PERCENT : FeeType::NONE)->feeFrom($topUp);
        $includedFeeDebt = $form->includeFeeDebt ? min($feeDebt, max(0, $topUp - $fee)) : 0.0;

        return [
            'fee' => round($fee + $includedFeeDebt, 2),
            'cabinet' => round($topUp - $fee - $includedFeeDebt, 2),
            'includedFeeDebt' => round($includedFeeDebt, 2),
        ];
    }

    /**
     * @param  array{edit: bool, status: bool, invoice: bool}  $can
     */
    public function saveOperation(User $user, DrsOperationFormData $form, array $can): void
    {
        $operation = $this->paymentRepo->findOperation((int) $form->id, $this->visibleForUserId($user));

        if ($operation === null) {
            throw ValidationException::withMessages(['form' => 'Операция не найдена']);
        }

        $attributes = $this->checkboxAttributes($operation, $user, $form, $can);

        if ($can['edit']) {
            $this->validateForm($form, false);
            $isManual = $operation->payment->source === PaymentSource::MANUAL;
            $clientId = $isManual ? (int) $form->clientId : $operation->payment->client_id;
            $form->managerId = $clientId === $operation->payment->client_id && $operation->manager_id !== null
                ? $operation->manager_id
                : $this->getClientManagerId($clientId);
            $form->feeIncluded = $this->isClientFeeIncluded(
                $clientId,
                $isManual && $form->operationDate ? $form->operationDate : $operation->payment->received_date
            );
            $attributes += $this->moneyAttributes($form, $this->getClientFeeDebt($clientId, $operation->id));
            $attributes += $this->detailAttributes($form);

            if ($isManual) {
                $this->paymentRepo->updateManualPayment($operation->payment, [
                    'client_id' => $clientId,
                    'received_date' => $form->operationDate ?: $operation->payment->received_date,
                ]);
            } else {
                $attributes['credit_amount'] = round($form->creditAmount, 2);
            }
        }

        $this->paymentRepo->updateOperation($operation, $attributes);
    }

    /**
     * @param  array{edit: bool, status: bool, invoice: bool}  $can
     */
    public function createCredit(User $user, DrsOperationFormData $form, array $can): void
    {
        $form->managerId = $form->clientId === null ? null : $this->getClientManagerId($form->clientId);
        $this->validateForm($form, true);

        $clientId = (int) $form->clientId;
        $form->feeIncluded = $this->isClientFeeIncluded($clientId, $form->operationDate);
        $form->creditAmount = -abs($form->creditAmount);
        $attributes = ['credit_amount' => round($form->creditAmount, 2), 'opened_at' => now()]
            + $this->moneyAttributes($form, $this->getClientFeeDebt($clientId))
            + $this->detailAttributes($form);

        // Номер кредита = максимум + 1: без блокировки два одновременных сохранения получат один номер.
        Cache::lock('drs:manual-payment-number', 10)->block(5, fn () => $this->db->transaction(
            function () use ($user, $form, $can, $clientId, $attributes) {
                $operation = $this->paymentRepo->createManualOperation(
                    $clientId,
                    Carbon::parse($form->operationDate ?: $this->agencyToday()->toDateString()),
                    $attributes
                );
                $this->paymentRepo->updateOperation(
                    $operation,
                    $this->checkboxAttributes($operation, $user, $form, $can)
                );
            }
        ));
    }

    public function toggleFlag(User $user, int $operationId, string $flag, bool $value): void
    {
        if (! in_array($flag, ['is_sent_to_cabinet', 'is_fee_in_piggy_bank', 'is_invoice_issued'], true)) {
            return;
        }

        $operation = $this->paymentRepo->findOperation($operationId, $this->visibleForUserId($user));

        if ($operation === null) {
            return;
        }

        $attributes = [$flag => $value];

        if ($flag === 'is_sent_to_cabinet') {
            $attributes += $this->statusAttributes($operation, $user, $value);
        } elseif ($flag === 'is_invoice_issued') {
            $attributes += ['invoice_changed_at' => now(), 'invoice_changed_by' => $user->id];
        }

        $this->paymentRepo->updateOperation($operation, $attributes);
    }

    public function hideOperation(User $user, int $operationId): void
    {
        $operation = $this->paymentRepo->findOperation($operationId, $this->visibleForUserId($user));

        if ($operation !== null) {
            $this->paymentRepo->hideOperation($operation);
        }
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    public function getClientOptions(User $user): Collection
    {
        return $this->paymentRepo->getVisibleClients($this->visibleForUserId($user))
            ->map(fn (Client $client) => ['id' => $client->id, 'name' => $client->name])
            ->values();
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    public function getClientProjectOptions(int $clientId): Collection
    {
        return $this->paymentRepo->getClientProjects($clientId)
            ->map(fn (Project $project) => ['id' => $project->id, 'name' => $project->name])
            ->values();
    }

    public function getLastImportAt(): ?CarbonInterface
    {
        return $this->paymentRepo->getLastImportAt()
            ?->timezone($this->agencyTimezone());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getAdvertisingSystemOptions(): array
    {
        return array_map(
            fn (AdvertisingSystem $system) => ['value' => $system->value, 'label' => $system->value],
            AdvertisingSystem::cases()
        );
    }

    /**
     * Поступления из 1С без обработки дольше трех рабочих дней: уведомление менеджеру один раз.
     */
    public function notifyUnprocessed(?CarbonInterface $today = null): int
    {
        $receivedBefore = ($today ?? today())->copy()->subWeekdays(self::UNPROCESSED_WORKING_DAYS);
        $count = 0;

        foreach ($this->paymentRepo->getUnprocessedOperations($receivedBefore) as $operation) {
            $client = $operation->payment->client;
            $userId = $operation->manager_id ?? $client->manager_id;
            $project = $this->notificationProject($client);

            if ($userId !== null && $project !== null) {
                event(new FundsIncomeUnprocessed(
                    userId: $userId,
                    projectId: $project['id'],
                    projectName: $project['name'],
                    clientId: $client->id,
                    clientName: $client->name,
                    amount: $operation->bank_received_amount,
                    docNo: $operation->payment->number,
                    postedAt: Carbon::parse($operation->payment->received_date),
                ));
                $count++;
            }

            $this->paymentRepo->updateOperation($operation, ['unprocessed_notified_at' => now()]);
        }

        return $count;
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

        $this->applyDefaultFee($payment, $client);

        if ($payment->wasRecentlyCreated) {
            $this->notifyFundsReceived($payment, $client, (float) $data->Total);
        }
    }

    private function applyDefaultFee(Payment $payment, Client $client): void
    {
        $feeType = $this->clientFeeType($client, $payment->received_date);

        foreach ($this->paymentRepo->getPaymentOperations($payment) as $operation) {
            if ($operation->opened_at !== null || $operation->is_sent_to_cabinet) {
                continue;
            }

            $topUp = round($operation->bank_received_amount - $operation->credit_amount, 2);
            $fee = $feeType->feeFrom($topUp);

            $this->paymentRepo->updateOperation($operation, [
                'cabinet_top_up_amount' => $topUp,
                'fee_included' => $feeType === FeeType::THREE_PERCENT,
                'fee_amount' => $fee,
                'ad_cabinet_amount' => round($topUp - $fee, 2),
            ]);
        }
    }

    private function notifyFundsReceived(Payment $payment, Client $client, float $amount): void
    {
        $project = $this->notificationProject($client);

        if ($client->manager_id === null || $project === null) {
            return;
        }

        event(new FundsReceived(
            userId: $client->manager_id,
            projectId: $project['id'],
            projectName: $project['name'],
            clientId: $client->id,
            clientName: $client->name,
            amount: $amount,
            docNo: $payment->number,
            postedAt: Carbon::parse($payment->received_date),
        ));
    }

    /**
     * Клиенто-проект клиента с включенной интеграцией «1С движение рекламных средств».
     *
     * @return array{id: int, name: string}|null
     */
    private function notificationProject(Client $client): ?array
    {
        $project = $this->paymentRepo->findClientProjectWithIntegration($client->id, self::AD_BUDGET_FLOW_INTEGRATION);

        return $project === null ? null : ['id' => $project->id, 'name' => $project->name];
    }

    private function parseInvoices(DataCollection $invoices): DataCollection
    {
        return InvoiceData::collection(
            $invoices->toCollection()->map(function ($invoice) {
                return [
                    'invoice_number' => $invoice->invoiceNumber,
                    'invoice_date' => $invoice->invoiceDate,
                    'sum' => $invoice->sum,
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

    private function visibleForUserId(User $user): ?int
    {
        $seesAll = ClientsAndProjectsPermissions::userCanSeeAll($user)
            || $user->hasAnyPermission(['full '.PermissionGroup::ADVERTISING_FUNDS_MOVEMENT->value]);

        return $seesAll ? null : $user->id;
    }

    private function validateForm(DrsOperationFormData $form, bool $isCredit): void
    {
        $errors = [];

        if ($isCredit && round($form->creditAmount, 2) == 0.0) {
            $errors['form.creditAmount'] = 'Укажите сумму кредита';
        }

        if ($form->clientId === null) {
            $errors['form.clientId'] = 'Выберите клиента';
        }

        if ($isCredit && $form->clientId !== null && $form->managerId === null) {
            $errors['form.managerId'] = 'У клиента не назначен менеджер в «Клиенты и клиенто-проекты»';
        }

        if ($isCredit && AdvertisingSystem::tryFrom((string) $form->advertisingSystem) === null) {
            $errors['form.advertisingSystem'] = 'Выберите канал';
        }

        if ($form->topUpAmount < 0) {
            $errors['form.topUpAmount'] = 'Сумма пополнения не может быть отрицательной';
        }

        if ($form->projectId !== null && $form->clientId !== null
            && ! $this->projectBelongsToClient($form->projectId, $form->clientId)) {
            $errors['form.projectId'] = 'Клиенто-проект не относится к клиенту';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function projectBelongsToClient(int $projectId, int $clientId): bool
    {
        return $this->paymentRepo->getClientProjects($clientId)->contains('id', $projectId);
    }

    private function moneyAttributes(DrsOperationFormData $form, float $feeDebt): array
    {
        $calculated = $this->calculate($form, $feeDebt);

        return [
            'cabinet_top_up_amount' => max(0, round($form->topUpAmount, 2)),
            'fee_included' => $form->feeIncluded,
            'fee_amount' => $calculated['fee'],
            'included_fee_debt' => $calculated['includedFeeDebt'],
            'ad_cabinet_amount' => $calculated['cabinet'],
        ];
    }

    private function detailAttributes(DrsOperationFormData $form): array
    {
        return [
            'project_id' => $form->projectId,
            'manager_id' => $form->managerId,
            'advertising_system' => AdvertisingSystem::tryFrom((string) $form->advertisingSystem),
            'comment' => filled($form->comment) ? $form->comment : null,
            'ad_cabinet_sent_date' => $form->sentDate ?: null,
        ];
    }

    /**
     * @param  array{edit: bool, status: bool, invoice: bool}  $can
     */
    private function checkboxAttributes(PaymentOperation $operation, User $user, DrsOperationFormData $form, array $can): array
    {
        $attributes = [];

        if ($can['edit']) {
            $attributes['is_fee_in_piggy_bank'] = $form->isFeeInPiggyBank;
        }

        if ($can['status'] && $form->isSentToCabinet !== (bool) $operation->is_sent_to_cabinet) {
            $attributes['is_sent_to_cabinet'] = $form->isSentToCabinet;
            $attributes += $this->statusAttributes($operation, $user, $form->isSentToCabinet, $form->sentDate);
        }

        if ($can['invoice'] && $form->isInvoiceIssued !== (bool) $operation->is_invoice_issued) {
            $attributes['is_invoice_issued'] = $form->isInvoiceIssued;
            $attributes['invoice_changed_at'] = now();
            $attributes['invoice_changed_by'] = $user->id;
        }

        return $attributes;
    }

    private function statusAttributes(PaymentOperation $operation, User $user, bool $isSent, ?string $sentDate = null): array
    {
        $attributes = ['status_changed_at' => now(), 'status_changed_by' => $user->id];

        if ($isSent && ! filled($sentDate) && $operation->ad_cabinet_sent_date === null) {
            $attributes['ad_cabinet_sent_date'] = $this->agencyToday()->toDateString();
        }

        return $attributes;
    }

    private function toRowData(PaymentOperation $operation): DrsOperationData
    {
        $payment = $operation->payment;

        return new DrsOperationData(
            id: $operation->id,
            isManual: $payment->source === PaymentSource::MANUAL,
            number: $payment->number,
            operationDate: $payment->received_date->format('d.m.Y'),
            sentDate: $operation->ad_cabinet_sent_date?->format('d.m.Y'),
            bankAmount: $operation->bank_received_amount,
            creditAmount: $operation->credit_amount,
            paymentDetails: $operation->payment_details,
            advertisingSystem: $operation->advertising_system?->value,
            invoiceNumber: $operation->invoice_number,
            managerName: $this->userName($operation->manager),
            clientId: $payment->client_id,
            clientName: $payment->client?->name ?? '',
            projectName: $operation->project?->name,
            adCabinetAmount: $operation->ad_cabinet_amount,
            feeAmount: $operation->fee_amount,
            isSentToCabinet: $operation->is_sent_to_cabinet,
            statusChangedLabel: $this->changedLabel($operation->status_changed_at, $operation->statusChangedBy),
            isFeeInPiggyBank: $operation->is_fee_in_piggy_bank,
            isInvoiceIssued: $operation->is_invoice_issued,
            invoiceChangedLabel: $this->changedLabel($operation->invoice_changed_at, $operation->invoiceChangedBy),
            isNew: $operation->opened_at === null,
            comment: $operation->comment,
        );
    }

    private function changedLabel(?CarbonInterface $at, ?User $by): ?string
    {
        if ($at === null) {
            return null;
        }

        $name = $this->userName($by);

        return 'Изменено: '.$at->copy()->timezone($this->agencyTimezone())->format('d.m.Y, H:i')
            .($name !== null ? " ({$name})" : '');
    }

    public function getAgencyTimezoneLabel(): string
    {
        $timezone = $this->agencyTimezone();

        return TimeZoneDictionary::byIdentifier($timezone)['label'] ?? $timezone;
    }

    private function agencyTimezone(): string
    {
        return $this->agencyTimezone ??= $this->agencyRepository->getPrimaryTimeZone();
    }

    private function agencyToday(): Carbon
    {
        return Carbon::today($this->agencyTimezone());
    }

    private function userName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : $user->login;
    }
}
