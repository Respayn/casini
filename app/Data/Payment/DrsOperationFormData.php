<?php

namespace App\Data\Payment;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

/**
 * Поля окна операции ДРС: правка поступления из 1С и новый кредит.
 */
class DrsOperationFormData extends Data implements Wireable
{
    use WireableData;

    public ?int $id = null;

    public bool $isManual = true;

    public ?string $number = null;

    /** Y-m-d */
    public ?string $operationDate = null;

    /** Y-m-d */
    public ?string $sentDate = null;

    public float $bankAmount = 0;

    /** Минус - кредит выдан клиенту, плюс - клиент вернул кредит */
    public float $creditAmount = 0;

    public ?int $clientId = null;

    public ?int $projectId = null;

    public ?int $managerId = null;

    public ?string $advertisingSystem = null;

    public bool $feeIncluded = true;

    /** Пополнение кабинета без учета сбора */
    public float $topUpAmount = 0;

    /** Вычесть задолженность по сбору из этого платежа */
    public bool $includeFeeDebt = false;

    /** Задолженность по сбору, уже вычтенная в этой операции */
    public float $includedFeeDebt = 0;

    public bool $isSentToCabinet = false;

    public bool $isFeeInPiggyBank = false;

    public bool $isInvoiceIssued = false;

    public ?string $comment = null;

    public ?string $paymentDetails = null;
}
