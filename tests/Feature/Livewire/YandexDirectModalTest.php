<?php

namespace Tests\Feature\Livewire;

use App\Models\Agency;
use App\Models\Integration;
use App\Models\User;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexDirectModalTest extends TestCase
{
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
        $user = User::factory()->create();
        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        return $user;
    }

    #[Test]
    public function test_select_integration_opens_yandex_direct_modal(): void
    {
        $user = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct')
            ->assertSet('selectedIntegration.integration.code', 'yandex_direct')
            ->assertDispatched('modal-show');
    }

    #[Test]
    public function test_set_integration_settings_stores_client_login(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('setIntegrationSettings', $integration->id, [
                'is_enabled' => true,
                'client_login' => 'agency-client',
                'oauth_token' => 'token',
                'sync_enabled_at' => '2026-07-10',
            ])
            ->assertSet("integrationSettings.{$integration->id}.isEnabled", true)
            ->assertSet("integrationSettings.{$integration->id}.settings.client_login", 'agency-client');
    }

    #[Test]
    public function test_prepare_yandex_direct_oauth_returns_url(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct')
            ->call('prepareYandexDirectOAuth');

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
    public function test_prepare_yandex_direct_oauth_returns_error_when_not_configured(): void
    {
        config([
            'services.yandex_direct.client_id' => null,
            'services.yandex_direct.client_secret' => null,
        ]);

        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct')
            ->call('prepareYandexDirectOAuth');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('url', $result);
    }

    #[Test]
    public function test_is_yandex_direct_oauth_configured_reflects_env(): void
    {
        $user = $this->createUserWithAgency();

        config([
            'services.yandex_direct.client_id' => 'id',
            'services.yandex_direct.client_secret' => 'secret',
        ]);

        $configured = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct');

        $this->assertTrue($configured->instance()->isYandexDirectOAuthConfigured);
        $this->assertTrue($configured->instance()->isSelectedIntegrationPlatformConfigured);

        config([
            'services.yandex_direct.client_id' => null,
            'services.yandex_direct.client_secret' => null,
        ]);

        $unconfigured = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct');

        $this->assertFalse($unconfigured->instance()->isYandexDirectOAuthConfigured);
        $this->assertFalse($unconfigured->instance()->isSelectedIntegrationPlatformConfigured);
    }

    #[Test]
    public function test_pull_yandex_direct_oauth_result_returns_settings_and_clears_cache(): void
    {
        $user = $this->createUserWithAgency();
        $cacheDataId = 'lw-oauth-result';

        Cache::put('yandex_direct_oauth_result_'.$cacheDataId, [
            'oauth_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => '2026-07-13 12:00:00',
            'account_id' => 'account-1',
        ], now()->addMinutes(15));

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('pullYandexDirectOAuthResult', $cacheDataId);

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('settings', $result);
        $this->assertSame('access-token', $result['settings']['oauth_token']);
        $this->assertFalse(Cache::has('yandex_direct_oauth_result_'.$cacheDataId));

        $component->call('pullYandexDirectOAuthResult', $cacheDataId);
        $second = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($second);
        $this->assertTrue($second['pending'] ?? false);
        $this->assertArrayNotHasKey('settings', $second);
    }

    #[Test]
    public function test_finalize_yandex_direct_oauth_applies_tokens_and_opens_modal(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();
        $cacheDataId = 'oauth-session-uuid';

        Cache::put('yandex_direct_oauth_result_'.$cacheDataId, [
            'oauth_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => '2026-07-13 12:00:00',
            'account_id' => 'account-1',
        ], now()->addMinutes(15));

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Login' => 'client-a', 'ClientInfo' => 'Клиент А'],
                    ],
                ],
            ]),
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('finalizeYandexDirectOAuth', $cacheDataId);

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertTrue($result['applied'] ?? false);
        $this->assertFalse(Cache::has('yandex_direct_oauth_result_'.$cacheDataId));
        $component
            ->assertSet('selectedIntegration.integration.code', 'yandex_direct')
            ->assertSet('selectedIntegration.isEnabled', true)
            ->assertSet('selectedIntegration.settings.oauth_token', 'access-token')
            ->assertSet("integrationSettings.{$integration->id}.settings.oauth_token", 'access-token')
            ->assertSet('yandexDirectOAuthRevision', 1)
            ->assertDispatched('modal-show')
            ->assertDispatched('yandex-direct-oauth-applied');
    }

    #[Test]
    public function test_yandex_direct_oauth_received_event_applies_from_settings(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Login' => 'client-a', 'ClientInfo' => 'Клиент А'],
                    ],
                ],
            ]),
        ]);

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->dispatch(
                'yandex-direct-oauth-received',
                settings: [
                    'oauth_token' => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'token_expires_at' => '2026-07-13 12:00:00',
                    'account_id' => 'account-1',
                ],
                cacheDataId: null,
                integrationId: $integration->id,
            )
            ->assertSet('selectedIntegration.integration.code', 'yandex_direct')
            ->assertSet('selectedIntegration.isEnabled', true)
            ->assertSet('selectedIntegration.settings.oauth_token', 'access-token')
            ->assertSet('yandexDirectOAuthRevision', 1)
            ->assertDispatched('yandex-direct-oauth-applied')
            ->assertDispatched('modal-show');
    }

    #[Test]
    public function test_prepare_yandex_direct_oauth_supports_redirect_mode(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct')
            ->call('prepareYandexDirectOAuth', false);

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertStringContainsString('popup=0', $result['url']);
    }

    #[Test]
    public function test_apply_yandex_direct_oauth_from_broadcast_applies_tokens(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Login' => 'client-a', 'ClientInfo' => 'Клиент А'],
                    ],
                ],
            ]),
        ]);

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('applyYandexDirectOAuthFromBroadcast', [
                'oauth_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'token_expires_at' => '2026-07-13 12:00:00',
                'account_id' => 'account-1',
            ], $integration->id)
            ->assertSet('selectedIntegration.integration.code', 'yandex_direct')
            ->assertSet('selectedIntegration.isEnabled', true)
            ->assertSet('selectedIntegration.settings.oauth_token', 'access-token')
            ->assertDispatched('modal-show')
            ->assertDispatched('yandex-direct-oauth-applied');
    }

    #[Test]
    public function test_apply_yandex_direct_oauth_tokens_updates_selected_integration(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Login' => 'client-a', 'ClientInfo' => 'Клиент А'],
                    ],
                ],
            ]),
        ]);

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct')
            ->call('applyYandexDirectOAuthTokens', [
                'oauth_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'token_expires_at' => '2026-07-13 12:00:00',
                'account_id' => 'account-1',
            ])
            ->assertSet('selectedIntegration.isEnabled', true)
            ->assertSet('selectedIntegration.settings.oauth_token', 'access-token')
            ->assertSet("integrationSettings.{$integration->id}.isEnabled", true)
            ->assertSet("integrationSettings.{$integration->id}.settings.oauth_token", 'access-token')
            ->assertSet('yandexDirectOAuthRevision', 1);
    }

    #[Test]
    public function test_load_yandex_direct_logins_returns_options(): void
    {
        $user = $this->createUserWithAgency();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Login' => 'client-a', 'ClientInfo' => 'Клиент А'],
                    ],
                ],
            ]),
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('loadYandexDirectLogins', 'oauth-token');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('logins', $result);
        $this->assertSame('client-a', $result['logins'][0]['value']);
        $this->assertSame('Клиент А (client-a)', $result['logins'][0]['label']);
    }

    #[Test]
    public function test_load_yandex_direct_logins_empty_token_returns_error(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('loadYandexDirectLogins', '');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function test_load_yandex_direct_logins_agency_error_returns_specific_message(): void
    {
        $user = $this->createUserWithAgency();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'error' => [
                    'error_code' => 152,
                    'error_string' => 'Insufficient privileges',
                ],
            ], 200),
            'api.direct.yandex.com/json/v5/clients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Type' => 'AGENCY', 'Login' => 'agency-rep'],
                    ],
                ],
            ]),
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('loadYandexDirectLogins', 'oauth-token');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayNotHasKey('logins', $result);
        $this->assertStringContainsString('клиентов агентства', $result['error']);
    }

    #[Test]
    public function test_delete_integration_button_visible_after_oauth_without_client_login(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'error' => [
                    'error_code' => 58,
                    'error_string' => 'Незавершенная регистрация',
                ],
            ], 200),
            'api.direct.yandex.com/json/v5/clients' => Http::response([
                'error' => [
                    'error_code' => 58,
                    'error_string' => 'Незавершенная регистрация',
                ],
            ], 200),
        ]);

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct')
            ->call('applyYandexDirectOAuthTokens', [
                'oauth_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'token_expires_at' => '2026-07-13 12:00:00',
                'account_id' => 'account-1',
            ])
            ->assertSet('selectedIntegration.isEnabled', true)
            ->assertSet('selectedIntegration.settings.oauth_token', 'access-token')
            ->assertSet("integrationSettings.{$integration->id}.settings.oauth_token", 'access-token')
            ->assertSee('Удалить интеграцию');
    }

    #[Test]
    public function test_remove_integration_clears_yandex_direct_settings(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Login' => 'client-a', 'ClientInfo' => 'Клиент А'],
                    ],
                ],
            ]),
        ]);

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct')
            ->call('applyYandexDirectOAuthTokens', [
                'oauth_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'token_expires_at' => '2026-07-13 12:00:00',
                'account_id' => 'account-1',
            ])
            ->call('removeIntegration', $integration->id)
            ->tap(function ($component) use ($integration) {
                $this->assertFalse(
                    $component->get('integrationSettings')->has($integration->id),
                    'Настройки Яндекс.Директ должны быть удалены из integrationSettings'
                );
            });
    }

    #[Test]
    public function test_modal_shows_yandex_oauth_profile_card(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('setIntegrationSettings', $integration->id, [
                'is_enabled' => true,
                'oauth_token' => 'access-token',
                'oauth_yandex_login' => 'agency-user',
                'oauth_yandex_display_name' => 'Агентство Пример',
                'oauth_yandex_avatar_url' => 'https://avatars.yandex.net/get-yapic/0/0-0/islands-200',
            ])
            ->call('selectIntegration', 'yandex_direct')
            ->assertSet('selectedIntegration.settings.oauth_yandex_login', 'agency-user')
            ->assertSet('selectedIntegration.settings.oauth_yandex_display_name', 'Агентство Пример')
            ->assertSee('Авторизован для доступа к API Директа')
            ->assertSee('Выбрать другую учетную запись')
            ->assertSee('bg-blue-50', false);
    }

    #[Test]
    public function test_load_yandex_direct_oauth_profile_backfills_settings(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        Http::fake([
            'login.yandex.ru/info*' => Http::response([
                'id' => '100500',
                'login' => 'agency-user',
                'display_name' => 'Агентство Пример',
                'default_avatar_id' => '0/abc-0',
            ]),
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_direct')
            ->call('loadYandexDirectOAuthProfile', 'access-token');

        $result = ($component->effects['returns'] ?? [])[0] ?? null;

        $this->assertIsArray($result);
        $this->assertSame('agency-user', $result['profile']['oauth_yandex_login'] ?? null);
        $this->assertSame(
            'Агентство Пример',
            $result['profile']['oauth_yandex_display_name'] ?? null
        );
        $this->assertSame(
            'agency-user',
            $component->get("integrationSettings.{$integration->id}.settings.oauth_yandex_login")
        );
    }
}
