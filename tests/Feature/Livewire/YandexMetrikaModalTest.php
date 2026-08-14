<?php

namespace Tests\Feature\Livewire;

use App\Enums\AttributionModel;
use App\Models\Agency;
use App\Models\Integration;
use App\Models\User;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class YandexMetrikaModalTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegrationSeeder::class);

        config([
            'services.yandex_metrika.client_id' => 'test-metrika-client-id',
            'services.yandex_metrika.client_secret' => 'test-metrika-client-secret',
            'services.yandex_metrika.redirect_uri' => 'https://example.test/yandex-metrika/callback',
        ]);
    }

    private function createUserWithAgency(): User
    {
        $user = User::factory()->create();
        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        Permission::findOrCreate('full clients and projects all', 'web');
        $user->givePermissionTo('full clients and projects all');
        $user->load('permissions', 'roles');

        return $user;
    }

    private function fakeCountersResponse(): void
    {
        Http::fake([
            'api-metrika.yandex.net/management/v1/counters*' => Http::response([
                'counters' => [
                    [
                        'id' => 12345678,
                        'site' => 'example.ru',
                        'name' => 'Example',
                    ],
                    [
                        'id' => 87654321,
                        'site2' => ['site' => 'shop.test'],
                    ],
                ],
            ]),
        ]);
    }

    #[Test]
    public function test_select_integration_opens_yandex_metrika_modal(): void
    {
        $user = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_metrika')
            ->assertSet('selectedIntegration.integration.code', 'yandex_metrika')
            ->assertDispatched('modal-show')
            ->assertSee('Номер счетчика')
            ->assertSee('Сначала включите синхронизацию')
            ->assertSee('Автоматическая атрибуция')
            ->assertSee('Без роботов')
            ->assertSee('Добавить фильтр по странице входа')
            ->assertSee('Достижение целей из отчета Поисковые системы')
            ->assertSee('Достижение целей из отчета Директ, сводка')
            ->assertSee('Переходы из отчета Поисковые системы')
            ->assertSee('Переходы из отчета Поисковые запросы')
            ->assertSee('Может быть выбран только один источник достижения целей')
            ->assertSee('Доступен только для клиенто-проектов с типом SEO-продвижение');
    }

    #[Test]
    public function test_set_integration_settings_stores_counter_filters_and_reports(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_metrika')->firstOrFail();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('setIntegrationSettings', $integration->id, [
                'is_enabled' => true,
                'oauth_token' => 'token',
                'counter_id' => 12345678,
                'counter_domain' => 'example.ru',
                'attribution_model' => AttributionModel::AUTOMATIC->value,
                'data_mode' => 'without_robots',
                'filters' => [
                    'entry_page' => '!*promo*',
                    'last_search_phrase' => null,
                    'geo' => 'Москва',
                ],
                'reports' => [
                    'goals_search_engines' => true,
                    'goals_utm' => false,
                    'goals_conversions' => false,
                    'goals_direct_summary' => false,
                    'visits_search_engines' => true,
                    'visits_search_queries' => false,
                    'visits_geo' => false,
                ],
            ])
            ->assertSet("integrationSettings.{$integration->id}.isEnabled", true)
            ->assertSet("integrationSettings.{$integration->id}.settings.counter_id", 12345678)
            ->assertSet("integrationSettings.{$integration->id}.settings.attribution_model", 'automatic')
            ->assertSet("integrationSettings.{$integration->id}.settings.data_mode", 'without_robots')
            ->assertSet("integrationSettings.{$integration->id}.settings.filters.entry_page", '!*promo*')
            ->assertSet("integrationSettings.{$integration->id}.settings.reports.goals_search_engines", true)
            ->assertSet("integrationSettings.{$integration->id}.settings.reports.visits_search_engines", true);
    }

    #[Test]
    public function test_prepare_yandex_metrika_oauth_returns_url(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_metrika')
            ->call('prepareYandexMetrikaOAuth');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('cache_data_id', $result);
        $this->assertNotEmpty($result['cache_data_id']);
        $this->assertStringContainsString('popup=1', $result['url']);
        $this->assertStringContainsString(
            'cache_data_id='.urlencode($result['cache_data_id']),
            $result['url']
        );
    }

    #[Test]
    public function test_prepare_yandex_metrika_oauth_returns_error_when_not_configured(): void
    {
        config([
            'services.yandex_metrika.client_id' => null,
            'services.yandex_metrika.client_secret' => null,
        ]);

        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_metrika')
            ->call('prepareYandexMetrikaOAuth');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('url', $result);
    }

    #[Test]
    public function test_is_yandex_metrika_oauth_configured_reflects_env(): void
    {
        $user = $this->createUserWithAgency();

        $configured = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_metrika');

        $this->assertTrue($configured->instance()->isYandexMetrikaOAuthConfigured);
        $this->assertTrue($configured->instance()->isSelectedIntegrationPlatformConfigured);

        config([
            'services.yandex_metrika.client_id' => null,
            'services.yandex_metrika.client_secret' => null,
        ]);

        $unconfigured = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_metrika');

        $this->assertFalse($unconfigured->instance()->isYandexMetrikaOAuthConfigured);
        $this->assertFalse($unconfigured->instance()->isSelectedIntegrationPlatformConfigured);
    }

    #[Test]
    public function test_pull_yandex_metrika_oauth_result_returns_settings_and_clears_cache(): void
    {
        $user = $this->createUserWithAgency();
        $cacheDataId = 'lw-metrika-oauth-result';

        Cache::put('yandex_metrika_oauth_result_'.$cacheDataId, [
            'oauth_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => '2026-08-14 12:00:00',
        ], now()->addMinutes(15));

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('pullYandexMetrikaOAuthResult', $cacheDataId);

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('settings', $result);
        $this->assertSame('access-token', $result['settings']['oauth_token']);
        $this->assertFalse(Cache::has('yandex_metrika_oauth_result_'.$cacheDataId));
    }

    #[Test]
    public function test_finalize_yandex_metrika_oauth_applies_tokens_and_opens_modal(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_metrika')->firstOrFail();
        $cacheDataId = 'metrika-oauth-session';

        Cache::put('yandex_metrika_oauth_result_'.$cacheDataId, [
            'oauth_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => '2026-08-14 12:00:00',
        ], now()->addMinutes(15));

        $this->fakeCountersResponse();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('finalizeYandexMetrikaOAuth', $cacheDataId);

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertTrue($result['applied'] ?? false);
        $this->assertFalse(Cache::has('yandex_metrika_oauth_result_'.$cacheDataId));
        $component
            ->assertSet('selectedIntegration.integration.code', 'yandex_metrika')
            ->assertSet('selectedIntegration.isEnabled', true)
            ->assertSet('selectedIntegration.settings.oauth_token', 'access-token')
            ->assertSet("integrationSettings.{$integration->id}.settings.oauth_token", 'access-token')
            ->assertDispatched('modal-show')
            ->assertDispatched('yandex-metrika-oauth-applied');
    }

    #[Test]
    public function test_load_yandex_metrika_counters_returns_id_and_domain_labels(): void
    {
        $user = $this->createUserWithAgency();
        $this->fakeCountersResponse();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('loadYandexMetrikaCounters', 'oauth-token');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('counters', $result);
        $this->assertSame('12345678', $result['counters'][0]['value']);
        $this->assertSame('12345678 (example.ru)', $result['counters'][0]['label']);
        $this->assertSame('87654321 (shop.test)', $result['counters'][1]['label']);
    }

    #[Test]
    public function test_load_yandex_metrika_counters_empty_token_returns_error(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('loadYandexMetrikaCounters', '');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function test_delete_integration_button_visible_after_oauth_without_counter(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_metrika')->firstOrFail();
        $this->fakeCountersResponse();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_metrika')
            ->call('applyYandexMetrikaOAuthTokens', [
                'oauth_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'token_expires_at' => '2026-08-14 12:00:00',
            ])
            ->assertSet('selectedIntegration.isEnabled', true)
            ->assertSet("integrationSettings.{$integration->id}.settings.oauth_token", 'access-token')
            ->assertSee('Удалить интеграцию');
    }

    #[Test]
    public function test_attribution_options_use_dictionary_labels(): void
    {
        $labels = array_column(AttributionModel::options(), 'label');

        $this->assertContains('Автоматическая атрибуция', $labels);
        $this->assertContains('Последний переход из Директа', $labels);
        $this->assertSame('last_yandex_direct_click', AttributionModel::options()[3]['value']);
    }
}
