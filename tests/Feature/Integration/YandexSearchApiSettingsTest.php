<?php

namespace Tests\Feature\Integration;

use App\Data\Integrations\IntegrationData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Models\Integration;
use App\Models\Project;
use App\Services\IntegrationService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexSearchApiSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegrationSeeder::class);
    }

    #[Test]
    public function test_saves_yandex_search_api_settings_to_database(): void
    {
        $project = Project::factory()->create();
        $integration = Integration::query()->where('code', 'yandex_search_api')->firstOrFail();

        $settingsPayload = [
            'sync_enabled_at' => '2026-06-17',
            'regions' => [
                ['code' => 213, 'phrases' => ['болт высокопрочный цена', 'болт гост 7798 70']],
            ],
        ];

        $projectIntegration = new ProjectIntegrationData();
        $projectIntegration->integration = IntegrationData::from($integration);
        $projectIntegration->isEnabled = true;
        $projectIntegration->settings = $settingsPayload;

        app(IntegrationService::class)->updateIntegrationsSettings(
            $project->id,
            collect([$integration->id => $projectIntegration])
        );

        $loaded = app(IntegrationService::class)
            ->getIntegrationSettingsForProject($project->id)
            ->get($integration->id);

        $this->assertTrue($loaded->isEnabled);
        $this->assertCount(1, $loaded->settings['regions']);
        $this->assertSame(213, $loaded->settings['regions'][0]['code']);
        $this->assertCount(2, $loaded->settings['regions'][0]['phrases']);
    }

    #[Test]
    public function test_loads_legacy_double_encoded_settings(): void
    {
        $project = Project::factory()->create();
        $integration = Integration::query()->where('code', 'yandex_search_api')->firstOrFail();

        $settingsPayload = [
            'sync_enabled_at' => '2026-06-17',
            'regions' => [
                ['code' => 213, 'phrases' => ['тестовая фраза']],
            ],
        ];

        DB::table('integration_project')->insert([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => json_encode(json_encode($settingsPayload)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $loaded = app(IntegrationService::class)
            ->getIntegrationSettingsForProject($project->id)
            ->get($integration->id);

        $this->assertTrue($loaded->isEnabled);
        $this->assertIsArray($loaded->settings);
        $this->assertCount(1, $loaded->settings['regions']);
        $this->assertSame(213, $loaded->settings['regions'][0]['code']);
        $this->assertSame(['тестовая фраза'], $loaded->settings['regions'][0]['phrases']);
    }
}
