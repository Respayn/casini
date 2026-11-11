<?php

namespace Tests\Unit\Services\ProjectFacts;

use App\Factories\CallibriFilterFactory;
use App\Models\CallibriLead;
use App\Models\Client;
use App\Models\Project;
use App\Models\YandexDirectDailySpending;
use App\Models\YandexMetrikaGoalUtm;
use App\Models\YandexMetrikaSearchEnginesStats;
use App\Models\YandexSearchApiDailyTopPercent;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\ProjectFacts\ProjectKpiFactsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ProjectKpiFactsServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('yandex_direct_daily_spendings')
            || ! Schema::hasTable('callibri_leads')
            || ! Schema::hasTable('yandex_metrika_goal_utms')
            || ! Schema::hasTable('yandex_search_api_daily_top_percents')
            || ! Schema::hasTable('yandex_metrika_search_engines_stats')
        ) {
            $this->markTestSkipped('Нет таблиц фактов KPI в локальной БД (нужна MariaDB как на staging)');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeProject(): Project
    {
        $client = Client::factory()->create(['manager_id' => null]);
        $departmentId = Schema::hasTable('departments')
            ? \DB::table('departments')->value('id')
            : null;

        return Project::factory()->create(array_filter([
            'client_id' => $client->id,
            'specialist_id' => null,
            'department_id' => $departmentId,
        ], static fn ($v) => $v !== null));
    }

    public function test_direct_budget_sums_daily_spendings_with_vat(): void
    {
        $project = $this->makeProject();
        YandexDirectDailySpending::query()->create([
            'project_id' => $project->id,
            'date' => '2026-10-01',
            'cost_with_vat' => 100.11,
            'cost_without_vat' => 80.0,
        ]);
        YandexDirectDailySpending::query()->create([
            'project_id' => $project->id,
            'date' => '2026-10-15',
            'cost_with_vat' => 200.22,
            'cost_without_vat' => 160.0,
        ]);

        $service = new ProjectKpiFactsService(
            Mockery::mock(IntegrationProjectCredentials::class),
            app(CallibriFilterFactory::class),
        );

        $sum = $service->directBudget(
            $project->id,
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31'),
            true,
        );

        $this->assertSame(300.33, $sum);
    }

    public function test_leads_total_uses_callibri_and_metrika_after_filters(): void
    {
        $project = $this->makeProject();

        CallibriLead::query()->create([
            'project_id' => $project->id,
            'external_id' => 'in',
            'date' => '2026-10-05 10:00:00',
            'utm_source' => 'yandex',
            'utm_campaign' => '100',
            'utm_medium' => 'cpc',
        ]);
        CallibriLead::query()->create([
            'project_id' => $project->id,
            'external_id' => 'out',
            'date' => '2026-10-05 11:00:00',
            'utm_source' => 'google',
            'utm_campaign' => '200',
            'utm_medium' => 'cpc',
        ]);
        YandexMetrikaGoalUtm::query()->create([
            'project_id' => $project->id,
            'goal_name' => 'Заявка',
            'achieved_date' => '2026-10-06',
            'utm_source' => 'yandex',
            'utm_medium' => 'cpc',
            'utm_campaign' => '100',
        ]);
        YandexMetrikaGoalUtm::query()->create([
            'project_id' => $project->id,
            'goal_name' => 'Заявка',
            'achieved_date' => '2026-10-06',
            'utm_source' => 'vk',
            'utm_medium' => 'cpc',
            'utm_campaign' => '300',
        ]);

        $credentials = Mockery::mock(IntegrationProjectCredentials::class);
        $credentials->shouldReceive('hasEnabled')->with($project->id, 'callibri')->andReturn(true);
        $credentials->shouldReceive('hasEnabled')->with($project->id, 'yandex_metrika')->andReturn(true);
        $credentials->shouldReceive('settingsFor')->with($project->id, 'callibri')->andReturn([
            'utm_filter_mode' => 'source',
            'utm_source' => 'yandex',
            'appeals_type' => ['all'],
            'lead_cost' => 'all',
        ]);
        $credentials->shouldReceive('settingsFor')->with($project->id, 'yandex_metrika')->andReturn([
            'reports' => ['goals_utm' => true],
            'utm_filter_mode' => 'source',
            'utm_source' => 'yandex',
        ]);

        $service = new ProjectKpiFactsService($credentials, app(CallibriFilterFactory::class));
        $total = $service->leadsTotal(
            $project->id,
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31'),
        );

        // 1 Callibri (yandex) + 1 Metrika (yandex); google/vk отфильтрованы
        $this->assertSame(2, $total);
    }

    public function test_top10_and_seo_facts_match_shared_sources(): void
    {
        $project = $this->makeProject();

        YandexSearchApiDailyTopPercent::query()->create([
            'project_id' => $project->id,
            'date' => '2026-10-01',
            'top_10_percent' => 40.0,
            'phrases_count' => 10,
        ]);
        YandexSearchApiDailyTopPercent::query()->create([
            'project_id' => $project->id,
            'date' => '2026-10-02',
            'top_10_percent' => 50.0,
            'phrases_count' => 10,
        ]);
        YandexMetrikaSearchEnginesStats::query()->create([
            'project_id' => $project->id,
            'search_engine' => 'yandex',
            'month' => '2026-10-01',
            'visits' => 5208,
            'conversions' => 60,
        ]);

        $service = new ProjectKpiFactsService(
            Mockery::mock(IntegrationProjectCredentials::class),
            app(CallibriFilterFactory::class),
        );

        $this->assertSame(
            45.0,
            $service->top10Average(
                $project->id,
                Carbon::parse('2026-10-01'),
                Carbon::parse('2026-10-31'),
            ),
        );
        $this->assertSame(
            5208,
            $service->seoSearchVisits(
                $project->id,
                Carbon::parse('2026-10-01'),
                Carbon::parse('2026-10-31'),
            ),
        );
        $this->assertSame(
            60,
            $service->seoSearchConversions(
                $project->id,
                Carbon::parse('2026-10-01'),
                Carbon::parse('2026-10-31'),
            ),
        );
    }
}
