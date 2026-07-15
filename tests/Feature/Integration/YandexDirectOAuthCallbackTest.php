<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Services\YandexDirectAuthService;
use App\Services\YandexDirectService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexDirectOAuthCallbackTest extends TestCase
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
    public function test_oauth_callback_popup_returns_post_message_view(): void
    {
        $user = User::factory()->create();

        $this->mock(YandexDirectAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->with('auth-code')
                ->andReturn([
                    'access_token' => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'expires_in' => 3600,
                ]);
        });

        Http::fake([
            'login.yandex.ru/info*' => Http::response([
                'id' => '100500',
                'login' => 'yandex-user',
                'display_name' => 'Яндекс Пользователь',
                'default_avatar_id' => '0/abc-0',
            ]),
        ]);

        $state = base64_encode(Crypt::encryptString(json_encode([
            'project_id' => 1,
            'cache_data_id' => 'lw-test',
            'popup' => true,
        ])));

        $response = $this->actingAs($user)->get(route('yandex_direct.oauth.callback', [
            'code' => 'auth-code',
            'state' => $state,
        ]));

        $response->assertOk();
        $response->assertViewIs('oauth.yandex-direct-popup-complete');
        $response->assertSee('yandex-direct-oauth', false);
        $response->assertSee('postMessage', false);
        $response->assertSee('access-token', false);

        $cached = Cache::get('yandex_direct_oauth_result_lw-test');
        $this->assertIsArray($cached);
        $this->assertSame('access-token', $cached['oauth_token'] ?? null);
        $this->assertSame('refresh-token', $cached['refresh_token'] ?? null);
        $this->assertSame('100500', $cached['oauth_yandex_user_id'] ?? null);
        $this->assertSame('yandex-user', $cached['oauth_yandex_login'] ?? null);
        $this->assertSame('Яндекс Пользователь', $cached['oauth_yandex_display_name'] ?? null);
        $this->assertSame(
            YandexDirectService::buildYandexAvatarUrl('0/abc-0'),
            $cached['oauth_yandex_avatar_url'] ?? null
        );
    }

    #[Test]
    public function test_oauth_callback_popup_invalid_grant_returns_error_view(): void
    {
        $user = User::factory()->create();

        $this->mock(YandexDirectAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->andThrow(new \RuntimeException('Failed to get access token: invalid_grant'));
        });

        $state = base64_encode(Crypt::encryptString(json_encode([
            'project_id' => 1,
            'cache_data_id' => 'lw-test',
            'popup' => true,
        ])));

        $response = $this->actingAs($user)->get(route('yandex_direct.oauth.callback', [
            'code' => 'expired-code',
            'state' => $state,
        ]));

        $response->assertStatus(400);
        $response->assertViewIs('oauth.yandex-direct-oauth-unconfigured');
        $response->assertSee('Код авторизации уже использован или истёк', false);
        $response->assertSee('yandex-direct-oauth-error', false);
    }

    #[Test]
    public function test_oauth_callback_without_popup_redirects_to_project_form(): void
    {
        $user = User::factory()->create();

        $this->mock(YandexDirectAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->andReturn([
                    'access_token' => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'expires_in' => 3600,
                ]);
        });

        Http::fake([
            'login.yandex.ru/info*' => Http::response([
                'id' => '100500',
                'login' => 'yandex-user',
                'display_name' => 'Яндекс Пользователь',
                'default_avatar_id' => '0/abc-0',
            ]),
        ]);

        $state = base64_encode(Crypt::encryptString(json_encode([
            'project_id' => 42,
            'cache_data_id' => 'lw-test',
            'popup' => false,
        ])));

        $response = $this->actingAs($user)->get(route('yandex_direct.oauth.callback', [
            'code' => 'auth-code',
            'state' => $state,
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString(
            'clients-and-projects/project',
            $location
        );
        $this->assertStringContainsString('state=', $location);

        parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $query);
        $this->assertNotEmpty($query['state'] ?? null);

        $decoded = json_decode(
            Crypt::decryptString(base64_decode($query['state'])),
            true
        );
        $this->assertSame('yandex_direct', $decoded['open_integration'] ?? null);
        $this->assertNotEmpty($decoded['integrations'] ?? null);

        $integrationSettings = $decoded['integrations'][0]['settings'] ?? [];
        $clientLogin = $integrationSettings['client_login']
            ?? $integrationSettings['clientLogin']
            ?? null;
        $this->assertTrue($clientLogin === null || $clientLogin === '');
        $this->assertSame(
            'access-token',
            $integrationSettings['oauth_token']
                ?? $integrationSettings['encryptedOauthToken']
                ?? $integrationSettings['encrypted_oauth_token']
                ?? null
        );
    }

    #[Test]
    public function test_oauth_callback_popup_writes_local_storage_done_key(): void
    {
        $user = User::factory()->create();

        $this->mock(YandexDirectAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->andReturn([
                    'access_token' => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'expires_in' => 3600,
                ]);
        });

        Http::fake([
            'login.yandex.ru/info*' => Http::response([
                'id' => '100500',
                'login' => 'yandex-user',
                'display_name' => 'Яндекс Пользователь',
                'default_avatar_id' => '0/abc-0',
            ]),
        ]);

        $state = base64_encode(Crypt::encryptString(json_encode([
            'project_id' => 1,
            'cache_data_id' => 'lw-storage-test',
            'popup' => true,
        ])));

        $response = $this->actingAs($user)->get(route('yandex_direct.oauth.callback', [
            'code' => 'auth-code',
            'state' => $state,
        ]));

        $response->assertOk();
        $response->assertSee('casini_yandex_direct_oauth_done', false);
        $response->assertSee('lw-storage-test', false);
        $response->assertSee('localStorage', false);
        $response->assertSee('3000', false);
    }
}
