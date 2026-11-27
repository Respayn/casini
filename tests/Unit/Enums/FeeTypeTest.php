<?php

namespace Tests\Unit\Enums;

use App\Data\Payment\DrsOperationFormData;
use App\Enums\FeeType;
use App\Services\PaymentService;
use Tests\TestCase;

class FeeTypeTest extends TestCase
{
    public function test_three_percent_fee_is_inside_top_up(): void
    {
        $this->assertSame(2257.28, FeeType::THREE_PERCENT->feeFrom(77500));
        $this->assertSame(291.26, FeeType::THREE_PERCENT->feeFrom(10000));
    }

    public function test_none_and_empty_top_up_have_no_fee(): void
    {
        $this->assertSame(0.0, FeeType::NONE->feeFrom(2247312));
        $this->assertSame(0.0, FeeType::THREE_PERCENT->feeFrom(0));
    }

    public function test_calculate_uses_credit_return_and_fee_debt(): void
    {
        $service = (new \ReflectionClass(PaymentService::class))->newInstanceWithoutConstructor();

        $form = DrsOperationFormData::from(['topUpAmount' => 415000 - 80000, 'feeIncluded' => true]);
        $this->assertSame(
            ['fee' => 9757.28, 'cabinet' => 325242.72, 'includedFeeDebt' => 0.0],
            $service->calculate($form, 24098.85)
        );

        $form->includeFeeDebt = true;
        $this->assertSame(
            ['fee' => 33856.13, 'cabinet' => 301143.87, 'includedFeeDebt' => 24098.85],
            $service->calculate($form, 24098.85)
        );
    }
}
