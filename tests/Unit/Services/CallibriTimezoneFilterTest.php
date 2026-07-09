<?php

namespace Tests\Unit\Services;

use App\Services\CallibriService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class CallibriTimezoneFilterTest extends TestCase
{
    public function test_support_example_lead_belongs_to_next_local_day(): void
    {
        $leads = [[
            'id' => 109480002,
            'date' => '2026-06-17T22:37:34.000Z',
            'type' => 'requests',
            'is_lid' => true,
        ]];

        $timezone = 'Asia/Yekaterinburg';
        $june17 = Carbon::createFromFormat('Y-m-d', '2026-06-17');
        $june18 = Carbon::createFromFormat('Y-m-d', '2026-06-18');

        $filteredFor17 = $this->filterLeadsByLocalDate($leads, $june17, $june17, $timezone);
        $filteredFor18 = $this->filterLeadsByLocalDate($leads, $june18, $june18, $timezone);

        $this->assertCount(0, $filteredFor17);
        $this->assertCount(1, $filteredFor18);
    }

    public function test_lead_without_date_is_excluded(): void
    {
        $leads = [['id' => 1, 'type' => 'calls']];
        $date = Carbon::createFromFormat('Y-m-d', '2026-06-17');

        $filtered = $this->filterLeadsByLocalDate($leads, $date, $date, 'Europe/Moscow');

        $this->assertCount(0, $filtered);
    }

    /**
     * @param array<int, array<string, mixed>> $leads
     * @return array<int, array<string, mixed>>
     */
    private function filterLeadsByLocalDate(
        array $leads,
        Carbon $from,
        Carbon $to,
        string $timezone
    ): array {
        $method = new ReflectionMethod(CallibriService::class, 'filterLeadsByLocalDate');
        $method->setAccessible(true);

        return $method->invoke(app(CallibriService::class), $leads, $from, $to, $timezone);
    }
}
