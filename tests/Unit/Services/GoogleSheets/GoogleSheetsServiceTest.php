<?php

namespace Tests\Unit\Services\GoogleSheets;

use App\Services\GoogleSheetsService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleSheetsServiceTest extends TestCase
{
    #[Test]
    public function it_detects_closed_month_by_agency_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-15 10:00:00', 'Europe/Moscow'));

        $service = app(GoogleSheetsService::class);

        $this->assertTrue($service->isClosedMonth(
            Carbon::parse('2026-08-01'),
            'Europe/Moscow',
        ));

        $this->assertFalse($service->isClosedMonth(
            Carbon::parse('2026-09-01'),
            'Europe/Moscow',
        ));

        Carbon::setTestNow();
    }
}
