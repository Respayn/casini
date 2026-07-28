<?php

namespace Tests\Feature\Integration;

use App\Data\Integrations\IntegrationData;
use App\Data\ProjectForm\ProjectIntegrationData;
use App\Models\Integration;
use App\Models\Project;
use App\Services\IntegrationService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexDirectSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegrationSeeder::class);
    }

    #[Test]
    public function test_saves_yandex_direct_settings_to_database(): void
    {
        $project = Project::factory()->create();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        $settingsPayload = [
            'client_login' => 'agency-client',
            'oauth_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => '2027-01-01 12:00:00',
            'sync_enabled_at' => '2026-07-10',
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
        $this->assertSame('agency-client', $loaded->settings['client_login']);
        $this->assertSame('access-token', $loaded->settings['oauth_token']);
        $this->assertSame('2026-07-10', $loaded->settings['sync_enabled_at']);
    }
}
