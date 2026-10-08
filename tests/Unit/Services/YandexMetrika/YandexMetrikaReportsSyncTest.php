<?php

namespace Tests\Unit\Services\YandexMetrika;

use App\Repositories\AgencyRepository;
use App\Repositories\IntegrationRepository;
use App\Services\YandexMetrika\YandexMetrikaReportsSync;
use App\Services\YandexMetrikaService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Domain\YandexMetrika\YandexMetrikaRepositoryInterface;

class YandexMetrikaReportsSyncTest extends TestCase
{
    /**
     * @return array<string, array{?string, ?string, ?string}>
     */
    public static function timezoneCases(): array
    {
        return [
            'пояс агентства важнее пояса счётчика' => ['Asia/Yekaterinburg', 'Europe/Moscow', 'Asia/Yekaterinburg'],
            'без пояса агентства берётся пояс счётчика' => [null, 'Europe/Moscow', 'Europe/Moscow'],
            'без обоих поясов в API уходит null' => [null, null, null],
        ];
    }

    #[DataProvider('timezoneCases')]
    public function test_sync_report_passes_agency_or_counter_timezone(
        ?string $agencyTimezone,
        ?string $counterTimezone,
        ?string $expectedTimezone,
    ): void {
        $agencyRepository = $this->createMock(AgencyRepository::class);
        $agencyRepository->expects($this->once())
            ->method('getProjectTimeZone')
            ->with(7)
            ->willReturn($agencyTimezone);

        $metrikaService = $this->createMock(YandexMetrikaService::class);
        $metrikaService->expects($this->once())->method('setupClientFromSettings');
        $metrikaService->expects($this->once())
            ->method('fetchSearchEnginesGoalsStats')
            ->with(
                $this->anything(),
                $this->anything(),
                [101],
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $expectedTimezone,
                $counterTimezone,
            )
            ->willReturn([]);

        $sync = new YandexMetrikaReportsSync(
            $metrikaService,
            $this->createStub(YandexMetrikaRepositoryInterface::class),
            $this->createStub(IntegrationRepository::class),
            $agencyRepository,
        );

        $sync->syncReport(
            7,
            ['goals' => [101], 'counter_time_zone' => $counterTimezone],
            YandexMetrikaReportsSync::GOALS_SEARCH_ENGINES,
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-30'),
        );
    }
}
