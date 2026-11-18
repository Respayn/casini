<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\YandexMetrikaVisitsGeo;
use App\Repositories\AgencyRepository;
use App\Repositories\IntegrationRepository;
use App\Services\IntegrationSync\YandexMetrikaSyncPeriod;
use App\Services\YandexMetrika\YandexMetrikaReportsSync;
use App\Services\YandexMetrikaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Src\Infrastructure\Persistence\Eloquent\EloquentYandexMetrikaRepository;
use Tests\TestCase;

class YandexMetrikaGeoVisitsSyncTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function test_sync_report_writes_visits_rows_for_geo(): void
    {
        $project = Project::factory()->create();
        $metrikaService = $this->createMock(YandexMetrikaService::class);
        $metrikaService->expects($this->once())->method('setupClientFromSettings');
        $metrikaService->expects($this->once())
            ->method('fetchGeoVisitsStats')
            ->willReturn([
                [
                    'city' => 'Москва',
                    'month' => '2026-08-01',
                    'visits' => 42,
                    'visitors' => 0,
                    'value' => 42,
                ],
            ]);

        $sync = new YandexMetrikaReportsSync(
            $metrikaService,
            app(EloquentYandexMetrikaRepository::class),
            $this->createStub(IntegrationRepository::class),
            app(AgencyRepository::class),
            new YandexMetrikaSyncPeriod,
        );

        $sync->syncReport(
            $project->id,
            [
                'oauth_token' => 'token',
                'oauth_yandex_login' => 'login',
                'counter_id' => 12345678,
                'visits_metric' => 'visits',
                'reports' => ['visits_geo' => true],
            ],
            YandexMetrikaReportsSync::VISITS_GEO,
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-18'),
        );

        $row = YandexMetrikaVisitsGeo::query()
            ->where('project_id', $project->id)
            ->where('city', 'Москва')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(42, $row->visits);
        $this->assertSame(0, $row->visitors);
        $this->assertSame(0, $row->goal_reaches);
    }

    #[Test]
    public function test_ready_reports_skips_geo_when_disabled(): void
    {
        $sync = new YandexMetrikaReportsSync(
            $this->createStub(YandexMetrikaService::class),
            $this->createStub(EloquentYandexMetrikaRepository::class),
            $this->createStub(IntegrationRepository::class),
            $this->createStub(AgencyRepository::class),
            new YandexMetrikaSyncPeriod,
        );

        $this->assertSame(
            [YandexMetrikaReportsSync::VISITS_SEARCH_QUERIES],
            $sync->readyReports([
                'oauth_token' => 'token',
                'counter_id' => 12345678,
                'sync_enabled_at' => '2026-08-01',
                'reports' => [
                    'visits_search_queries' => true,
                ],
            ]),
        );
        $this->assertSame(
            [YandexMetrikaReportsSync::VISITS_GEO],
            $sync->readyReports([
                'oauth_token' => 'token',
                'counter_id' => 12345678,
                'sync_enabled_at' => '2026-08-01',
                'reports' => [
                    'visits_geo' => true,
                ],
            ]),
        );
    }

    #[Test]
    public function test_upsert_does_not_overwrite_the_other_metric(): void
    {
        $project = Project::factory()->create();
        $repository = app(EloquentYandexMetrikaRepository::class);

        $repository->upsertVisitsGeo($project->id, 'Челябинск', '2026-09-01', 'visits', 10);
        $repository->upsertVisitsGeo($project->id, 'Челябинск', '2026-09-01', 'users', 5);

        $row = YandexMetrikaVisitsGeo::query()
            ->where('project_id', $project->id)
            ->where('city', 'Челябинск')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(10, $row->visits);
        $this->assertSame(5, $row->visitors);
        $this->assertSame(0, $row->goal_reaches);
    }
}
