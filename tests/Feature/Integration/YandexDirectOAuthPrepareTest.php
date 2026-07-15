<?php

namespace Tests\Feature\Integration;

use App\Models\Agency;
use App\Models\User;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexDirectOAuthPrepareTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegrationSeeder::class);

        config([
            'services.yandex_direct.client_id' => 'test-client-id',
            'services.yandex_direct.client_secret' => 'test-client-secret',
            'services.yandex_direct.redirect_uri' => 'https://example.test/yandex-direct/callback',
        ]);
    }

    #[Test]
    public function test_prepare_yandex_direct_oauth_returns_error_when_oauth_not_configured(): void
    {
        config([
            'services.yandex_direct.client_id' => null,
            'services.yandex_direct.client_secret' => null,
        ]);

        $user = User::factory()->create();
        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct')
            ->call('prepareYandexDirectOAuth');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('url', $result);
        $this->assertArrayNotHasKey('cache_data_id', $result);
    }

    #[Test]
    public function test_prepare_yandex_direct_oauth_returns_url_and_stores_cache(): void
    {
        $user = User::factory()->create();
        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct');

        $component->call('prepareYandexDirectOAuth');

        $returns = $component->effects['returns'] ?? [];
        $this->assertNotEmpty($returns);

        $result = $returns[0];
        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('cache_data_id', $result);
        $this->assertNotEmpty($result['cache_data_id']);
        $this->assertStringContainsString('yandex-direct/connect', $result['url']);
        $this->assertStringContainsString('popup=1', $result['url']);
        $this->assertStringContainsString(
            'cache_data_id='.urlencode($result['cache_data_id']),
            $result['url']
        );
        $this->assertTrue(Cache::has('integration_data_'.$result['cache_data_id']));
    }
}
