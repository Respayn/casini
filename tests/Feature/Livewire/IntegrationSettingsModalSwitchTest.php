<?php

namespace Tests\Feature\Livewire;

use App\Models\Integration;
use App\Models\User;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesUserWithClientProjectEdit;
use Tests\TestCase;

class IntegrationSettingsModalSwitchTest extends TestCase
{
    use CreatesUserWithClientProjectEdit;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegrationSeeder::class);

        config([
            'services.yandex_direct.use_sandbox' => false,
            'services.yandex_direct.api_url' => 'https://api.direct.yandex.com/json/v5/',
            'services.yandex_direct.client_id' => 'test-client-id',
            'services.yandex_direct.client_secret' => 'test-client-secret',
            'services.yandex_direct.redirect_uri' => 'https://example.test/yandex-direct/callback',
        ]);
    }

    private function createUserWithAgency(): User
    {
        return $this->createUserWithClientProjectEdit();
    }

    #[Test]
    public function test_switching_from_yandex_direct_to_callibri_renders_callibri_body(): void
    {
        $user = $this->createUserWithAgency();
        $directIntegration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('setIntegrationSettings', $directIntegration->id, [
                'is_enabled' => true,
                'oauth_token' => 'oauth-token',
                'oauth_yandex_login' => 'siteactiv',
                'oauth_yandex_display_name' => 'Компания СайтАктив',
                'sync_enabled_at' => '2026-07-15',
            ])
            ->call('selectIntegration', 'yandex_direct')
            ->assertSee('Авторизован для доступа к API Директа')
            ->call('selectIntegration', 'callibri')
            ->assertSet('selectedIntegration.integration.code', 'callibri')
            ->assertSet('integrationModalBodyRevision', 2)
            ->assertSee('API токен')
            ->assertDontSee('Авторизован для доступа к API Директа');
    }

    #[Test]
    public function test_switching_from_callibri_to_yandex_search_api_renders_search_api_body(): void
    {
        $user = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'callibri')
            ->assertSee('API токен')
            ->call('selectIntegration', 'yandex_search_api')
            ->assertSet('selectedIntegration.integration.code', 'yandex_search_api')
            ->assertSet('integrationModalBodyRevision', 2)
            ->assertSee('Добавить регион')
            ->assertDontSee('API токен');
    }

    #[Test]
    public function test_integration_modal_body_revision_increments_on_each_select(): void
    {
        $user = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->assertSet('integrationModalBodyRevision', 0)
            ->call('selectIntegration', 'yandex_direct')
            ->assertSet('integrationModalBodyRevision', 1)
            ->call('selectIntegration', 'callibri')
            ->assertSet('integrationModalBodyRevision', 2)
            ->call('selectIntegration', 'yandex_search_api')
            ->assertSet('integrationModalBodyRevision', 3);
    }
}
