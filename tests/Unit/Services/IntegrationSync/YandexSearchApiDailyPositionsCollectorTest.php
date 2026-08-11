<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Models\Agency;
use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Models\YandexSearchApiDailyTopPercent;
use App\Services\IntegrationSync\Collectors\YandexSearchApiDailyPositionsCollector;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use App\Services\YandexSearchApi\YandexSearchApiSerpSyncService;
use App\Services\YandexSearchApiService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class YandexSearchApiDailyPositionsCollectorTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_supports_project_requires_enabled_search_api_and_credentials(): void
    {
        config([
            'services.yandex_search_api.api_key' => 'test-key',
            'services.yandex_search_api.folder_id' => 'folder',
        ]);

        $integration = Integration::query()->where('code', 'yandex_search_api')->firstOrFail();
        $project = Project::factory()->create([
            'is_active' => true,
            'domain' => 'example.com',
        ]);

        IntegrationProject::query()->create([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => [
                'regions' => [
                    ['code' => 213, 'phrases' => ['купить холодильник']],
                ],
            ],
        ]);

        $collector = app(YandexSearchApiDailyPositionsCollector::class);

        $this->assertTrue($collector->supportsProject($project->id));
    }

    public function test_collect_range_for_past_day_skips_api_and_recalculates_from_positions(): void
    {
        config([
            'services.yandex_search_api.api_key' => 'test-key',
            'services.yandex_search_api.folder_id' => 'folder',
        ]);

        Agency::query()->orderBy('id')->first()
            ?->update(['time_zone' => 'Asia/Yekaterinburg']);

        $integration = Integration::query()->where('code', 'yandex_search_api')->firstOrFail();
        $project = Project::factory()->create([
            'is_active' => true,
            'domain' => 'example.com',
        ]);

        IntegrationProject::query()->create([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => [
                'regions' => [
                    ['code' => 213, 'phrases' => ['фраза один', 'фраза два']],
                ],
            ],
        ]);

        $serpSync = app(YandexSearchApiSerpSyncService::class);
        $taskIds = $serpSync->syncFromSettings($project->id, [
            ['code' => 213, 'phrases' => ['фраза один', 'фраза два']],
        ]);

        $pastDate = Carbon::parse('2026-08-01');
        foreach ($taskIds as $index => $taskId) {
            \App\Models\SerpPosition::query()->create([
                'serp_task_id' => $taskId,
                'check_date' => $pastDate->toDateString(),
                'position' => $index === 0 ? 3 : 20,
                'url' => $index === 0 ? 'https://example.com' : null,
            ]);
        }

        Http::fake();

        $collector = app(YandexSearchApiDailyPositionsCollector::class);
        $result = $collector->collectRange($project->id, $pastDate->copy(), $pastDate->copy());

        $this->assertTrue($result->ok);
        Http::assertNothingSent();

        $row = YandexSearchApiDailyTopPercent::query()
            ->where('project_id', $project->id)
            ->whereDate('date', $pastDate->toDateString())
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(50.0, (float) $row->top_10_percent);
        $this->assertSame(2, (int) $row->phrases_count);
    }
}
