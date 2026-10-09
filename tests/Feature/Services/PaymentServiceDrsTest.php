<?php

namespace Tests\Feature\Services;

use App\Data\Payment\DrsOperationFormData;
use App\Data\Payment\InvoiceData;
use App\Data\Payment\PaymentData;
use App\Enums\AdvertisingSystem;
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

        $credit = $service->newCreditForm($user);
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

    public function test_skipped_fee_becomes_client_fee_debt(): void
    {
        $user = User::factory()->create();
        $service = app(PaymentService::class);
        $service->processPayments([$this->paymentData('TEST-DRS-4', 10300)]);
        $operation = $this->operationByNumber('TEST-DRS-4');

        $form = $service->openOperation($user, $operation->id);
        $form->feeIncluded = false;
        $service->saveOperation($user, $form, self::CAN_ALL);

        $this->assertSame(300.0, $service->getClientFeeDebt($operation->payment->client_id));
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
