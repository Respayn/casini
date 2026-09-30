<?php

namespace Tests\Unit\Services\Agency;

use App\Models\Agency;
use App\Services\Agency\AgencyIdGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AgencyIdGeneratorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_generate_returns_four_digit_id(): void
    {
        $id = app(AgencyIdGenerator::class)->generate();

        $this->assertGreaterThanOrEqual(AgencyIdGenerator::MIN, $id);
        $this->assertLessThanOrEqual(AgencyIdGenerator::MAX, $id);
        $this->assertSame(4, strlen((string) $id));
    }

    public function test_generate_skips_existing_ids(): void
    {
        $takenId = AgencyIdGenerator::MIN;

        Agency::query()->updateOrCreate(
            ['id' => $takenId],
            [
                'name' => 'Taken Agency',
                'time_zone' => 'Europe/Moscow',
                'direct_budget_refresh_time' => '09:00:00',
            ]
        );

        $generator = app(AgencyIdGenerator::class);

        for ($i = 0; $i < 20; $i++) {
            $id = $generator->generate();
            $this->assertNotSame($takenId, $id);
            $this->assertGreaterThanOrEqual(AgencyIdGenerator::MIN, $id);
            $this->assertLessThanOrEqual(AgencyIdGenerator::MAX, $id);
        }
    }
}
