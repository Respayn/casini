<?php

namespace Tests\Feature\Services;

use App\Data\Payment\DrsOperationFormData;
use App\Data\Payment\InvoiceData;
use App\Data\Payment\PaymentData;
use App\Enums\AdvertisingSystem;
use App\Enums\FeeType;
use App\Enums\PaymentSource;
use App\Enums\PermissionGroup;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentOperation;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentServiceDrsTest extends TestCase
{
    use DatabaseTransactions;

    private const CAN_ALL = ['edit' => true, 'status' => true, 'invoice' => true];

    public function test_1c_resend_keeps_user_fields_and_applies_fee_by_default(): void
    {
        $service = app(PaymentService::class);
        $service->processPayments([$this->paymentData('TEST-DRS-1', 77500)]);

        $operation = $this->operationByNumber('TEST-DRS-1');
        $this->assertSame(77500.0, $operation->cabinet_top_up_amount);
        $this->assertSame(2257.28, $operation->fee_amount);
        $this->assertSame(75242.72, $operation->ad_cabinet_amount);

        $operation->update(['comment' => 'проверка', 'opened_at' => now()]);
        $service->processPayments([$this->paymentData('TEST-DRS-1', 77500)]);

        $this->assertSame('проверка', $operation->fresh()->comment);
        $this->assertSame(1, PaymentOperation::query()->where('payment_id', $operation->payment_id)->count());
    }

    public function test_credit_creates_debt_and_return_reduces_it(): void
    {
        $user = User::factory()->create();
        $service = app(PaymentService::class);
        $service->processPayments([$this->paymentData('TEST-DRS-2', 415000)]);
        $clientId = $this->operationByNumber('TEST-DRS-2')->payment->client_id;
        Client::query()->whereKey($clientId)->update(['manager_id' => $user->id]);

        $credit = $service->newCreditForm();
        $credit->clientId = $clientId;
        $credit->creditAmount = 100000;
        $credit->topUpAmount = 100000;
        $credit->advertisingSystem = AdvertisingSystem::Yandex->value;
        $service->createCredit($user, $credit, self::CAN_ALL);

        $this->assertSame(-100000.0, $service->getClientCreditDebt($clientId));

        $operation = $this->operationByNumber('TEST-DRS-2');
        $form = $service->openOperation($user, $operation->id);
        $this->assertInstanceOf(DrsOperationFormData::class, $form);
        $form->creditAmount = 80000;
        $form->topUpAmount = 415000 - 80000;
        $form->isSentToCabinet = true;
        $service->saveOperation($user, $form, self::CAN_ALL);

        $operation->refresh();
        $this->assertSame(325242.72, $operation->ad_cabinet_amount);
        $this->assertTrue($operation->is_sent_to_cabinet);
        $this->assertNotNull($operation->ad_cabinet_sent_date);
        $this->assertSame($user->id, $operation->status_changed_by);
        $this->assertSame(-20000.0, $service->getClientCreditDebt($clientId));
    }

    public function test_hidden_operation_keeps_1c_payment(): void
    {
        $user = User::factory()->create();
        $service = app(PaymentService::class);
        $service->processPayments([$this->paymentData('TEST-DRS-3', 1000)]);
        $operation = $this->operationByNumber('TEST-DRS-3');

        $service->hideOperation($user, $operation->id);

        $this->assertSoftDeleted($operation);
        $this->assertNotNull(Payment::query()->find($operation->payment_id));
    }

    public function test_fee_follows_client_fee_type_not_form(): void
    {
        $user = User::factory()->create();
        $service = app(PaymentService::class);
        $service->processPayments([$this->paymentData('TEST-DRS-4', 10300)]);
        $operation = $this->operationByNumber('TEST-DRS-4');

        $form = $service->openOperation($user, $operation->id);
        $form->feeIncluded = false;
        $service->saveOperation($user, $form, self::CAN_ALL);

        $operation->refresh();
        $this->assertTrue($operation->fee_included);
        $this->assertSame(300.0, $operation->fee_amount);
        $this->assertSame(0.0, $service->getClientFeeDebt($operation->payment->client_id));
    }

    public function test_credits_and_bank_payments_have_separate_numbering(): void
    {
        $user = User::factory()->create();
        $service = app(PaymentService::class);
        $bankNumber = (string) (Payment::withTrashed()->where('source', PaymentSource::MANUAL)->get()
            ->max(fn (Payment $payment) => (int) $payment->number) + 1);

        $credit = $service->newCreditForm();
        $credit->clientId = Client::query()->create(['name' => 'Тест ДРС нумерация', 'manager_id' => $user->id])->id;
        $credit->creditAmount = 1000;
        $credit->topUpAmount = 1000;
        $credit->advertisingSystem = AdvertisingSystem::Yandex->value;
        $service->createCredit($user, $credit, self::CAN_ALL);

        $manual = Payment::query()->where('source', PaymentSource::MANUAL)->latest('id')->first();
        $this->assertSame($bankNumber, $manual->number);

        $service->processPayments([$this->paymentData($bankNumber, 5000)]);
        $service->processPayments([$this->paymentData($bankNumber, 5000)]);

        $this->assertSame(1, Payment::query()->where('number', $bankNumber)->where('source', PaymentSource::FROM_1C)->count());
        $this->assertSame(1000.0, $manual->operations()->first()->cabinet_top_up_amount);

        $service->createCredit($user, $credit, self::CAN_ALL);
        $this->assertSame((string) ((int) $bankNumber + 1), Payment::query()->where('source', PaymentSource::MANUAL)->latest('id')->value('number'));
    }

    public function test_full_access_sees_operations_of_other_managers(): void
    {
        $service = app(PaymentService::class);
        $service->processPayments([$this->paymentData('TEST-DRS-5', 5000)]);
        $operation = $this->operationByNumber('TEST-DRS-5');
        $operation->payment->client->update(['manager_id' => User::factory()->create()->id]);

        $user = User::factory()->create();
        $visibleIds = fn () => $service->getMonthOperations($user->fresh(), now(), null, false)->pluck('id');
        $this->assertNotContains($operation->id, $visibleIds());

        $user->givePermissionTo(Permission::findOrCreate('full '.PermissionGroup::ADVERTISING_FUNDS_MOVEMENT->value));
        $this->assertContains($operation->id, $visibleIds());
    }

    public function test_client_without_fee_keeps_fee_before_change_date(): void
    {
        $user = User::factory()->create();
        $service = app(PaymentService::class);
        $client = Client::query()->create([
            'name' => 'Тест ДРС без сбора',
            'manager_id' => $user->id,
            'ad_fee_type' => FeeType::NONE,
            'ad_fee_changed_at' => today(),
        ]);

        foreach ([today()->subDay(), today()] as $date) {
            $credit = $service->newCreditForm();
            $credit->clientId = $client->id;
            $credit->operationDate = $date->toDateString();
            $credit->creditAmount = 10300;
            $credit->topUpAmount = 10300;
            $credit->advertisingSystem = AdvertisingSystem::Yandex->value;
            $service->createCredit($user, $credit, self::CAN_ALL);
        }

        $fees = PaymentOperation::query()
            ->whereHas('payment', fn ($query) => $query->where('client_id', $client->id))
            ->orderBy('id')
            ->pluck('fee_amount')
            ->all();
        $this->assertSame([300.0, 0.0], $fees);
    }

    private function operationByNumber(string $number): PaymentOperation
    {
        return PaymentOperation::query()
            ->whereHas('payment', fn ($query) => $query->where('number', $number))
            ->with('payment')
            ->firstOrFail();
    }

    private function paymentData(string $number, float $sum): PaymentData
    {
        $client = Client::query()->firstOrCreate(
            ['inn' => sprintf('%010d', crc32($number) % 10000000000)],
            ['name' => 'Тест ДРС '.$number]
        );

        return PaymentData::from([
            'PaymentNumber' => $number,
            'PaymentDate' => new \DateTimeImmutable('today'),
            'Payer' => $client->name,
            'InnPayer' => $client->inn,
            'ContractNumber' => null,
            'Total' => $sum,
            'Canceled' => false,
            'Purpose' => 'Оплата рекламы',
            'invoices' => InvoiceData::collection([[
                'invoice_number' => 'СЧ-'.$number,
                'invoice_date' => new \DateTimeImmutable('today'),
                'sum' => $sum,
            ]]),
        ]);
    }
}
