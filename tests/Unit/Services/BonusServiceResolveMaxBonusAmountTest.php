<?php

namespace Tests\Unit\Services;

use App\Models\ProjectBonusCondition;
use App\Models\ProjectBonusInterval;
use App\Services\BonusService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class BonusServiceResolveMaxBonusAmountTest extends TestCase
{
    private BonusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BonusService;
    }

    public function test_returns_null_when_bonuses_disabled(): void
    {
        $condition = $this->condition(false, false, 100000, [
            ['bonus_amount' => 5000, 'bonus_percentage' => null],
        ]);

        $this->assertNull($this->service->resolveMaxBonusAmount($condition));
    }

    public function test_returns_null_when_no_condition(): void
    {
        $this->assertNull($this->service->resolveMaxBonusAmount(null));
    }

    public function test_max_fixed_amount_including_negative_guarantee(): void
    {
        $condition = $this->condition(true, false, null, [
            ['bonus_amount' => 5000, 'bonus_percentage' => null],
            ['bonus_amount' => 12000, 'bonus_percentage' => null],
            ['bonus_amount' => -3000, 'bonus_percentage' => null],
        ]);

        $this->assertSame(12000.0, $this->service->resolveMaxBonusAmount($condition));
    }

    public function test_max_percent_of_client_payment(): void
    {
        $condition = $this->condition(true, true, 100000, [
            ['bonus_amount' => null, 'bonus_percentage' => 5],
            ['bonus_amount' => null, 'bonus_percentage' => 10],
            ['bonus_amount' => null, 'bonus_percentage' => -2],
        ]);

        // max of 5000, 10000, -2000
        $this->assertSame(10000.0, $this->service->resolveMaxBonusAmount($condition));
    }

    public function test_percent_mode_without_client_payment_returns_null(): void
    {
        $condition = $this->condition(true, true, null, [
            ['bonus_amount' => null, 'bonus_percentage' => 10],
        ]);

        $this->assertNull($this->service->resolveMaxBonusAmount($condition));
    }

    /**
     * @param  list<array{bonus_amount: mixed, bonus_percentage: mixed}>  $intervals
     */
    private function condition(
        bool $enabled,
        bool $inPercentage,
        int|float|null $clientPayment,
        array $intervals,
    ): ProjectBonusCondition {
        $condition = new ProjectBonusCondition([
            'bonuses_enabled' => $enabled,
            'calculate_in_percentage' => $inPercentage,
            'client_payment' => $clientPayment,
        ]);

        $intervalModels = new Collection;
        foreach ($intervals as $interval) {
            $intervalModels->push(new ProjectBonusInterval([
                'from_percentage' => 90,
                'to_percentage' => 100,
                'bonus_amount' => $interval['bonus_amount'],
                'bonus_percentage' => $interval['bonus_percentage'],
            ]));
        }

        $condition->setRelation('intervals', $intervalModels);

        return $condition;
    }
}
