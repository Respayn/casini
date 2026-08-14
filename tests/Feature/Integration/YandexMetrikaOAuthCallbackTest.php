<?php

namespace Tests\Feature\Integration;

use App\Models\IntegrationProject;
use App\Models\User;
use App\Services\YandexDirectService;
use App\Services\YandexMetrikaAuthService;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaOAuthCallbackTest extends TestCase
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

    #[Test]
    public function test_oauth_callback_popup_returns_post_message_view_without_writing_db(): void
    {
        $user = User::factory()->create();
        $before = IntegrationProject::query()->count();

        $this->mock(YandexMetrikaAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->with('auth-code')
                ->andReturn([
                    'access_token' => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'expires_in' => 3600,
                ]);
            $mock->shouldReceive('fetchOauthUserProfile')
                ->once()
                ->with('access-token')
                ->andReturn([
                    'oauth_yandex_user_id' => '100500',
                    'oauth_yandex_login' => 'yandex-user',
                    'oauth_yandex_display_name' => 'Яндекс Пользователь',
                    'oauth_yandex_avatar_url' => YandexDirectService::buildYandexAvatarUrl('0/abc-0'),
                ]);
        });

        $state = base64_encode(Crypt::encryptString(json_encode([
            'project_id' => 1,
            'cache_data_id' => 'lw-metrika-test',
            'popup' => true,
        ])));

        $response = $this->actingAs($user)->get(route('yandex-metrika.callback', [
            'code' => 'auth-code',
            'state' => $state,
        ]));

        $response->assertOk();
        $response->assertViewIs('oauth.yandex-metrika-popup-complete');
        $response->assertSee('yandex-metrika-oauth', false);
        $response->assertSee('postMessage', false);
        $response->assertSee('access-token', false);
        $response->assertDontSee('counter_id', false);

        $cached = Cache::get('yandex_metrika_oauth_result_lw-metrika-test');
        $this->assertIsArray($cached);
        $this->assertSame('access-token', $cached['oauth_token'] ?? null);
        $this->assertSame('refresh-token', $cached['refresh_token'] ?? null);
        $this->assertArrayNotHasKey('counter_id', $cached);
        $this->assertSame($before, IntegrationProject::query()->count());
    }

    #[Test]
    public function test_oauth_callback_popup_invalid_grant_returns_error_view(): void
    {
        $user = User::factory()->create();

        $this->mock(YandexMetrikaAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->andThrow(new \RuntimeException('Failed to get access token: invalid_grant'));
        });

        $state = base64_encode(Crypt::encryptString(json_encode([
            'project_id' => 1,
            'cache_data_id' => 'lw-metrika-test',
            'popup' => true,
        ])));

        $response = $this->actingAs($user)->get(route('yandex-metrika.callback', [
            'code' => 'expired-code',
            'state' => $state,
        ]));

        $response->assertStatus(400);
        $response->assertViewIs('oauth.yandex-metrika-oauth-unconfigured');
        $response->assertSee('Код авторизации уже использован или истёк', false);
        $response->assertSee('yandex-metrika-oauth-error', false);
    }

    #[Test]
    public function test_oauth_callback_without_popup_redirects_to_project_form(): void
    {
        $user = User::factory()->create();

        $this->mock(YandexMetrikaAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->andReturn([
                    'access_token' => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'expires_in' => 3600,
                ]);
            $mock->shouldReceive('fetchOauthUserProfile')
                ->once()
                ->andReturn([
                    'oauth_yandex_user_id' => '100500',
                    'oauth_yandex_login' => 'yandex-user',
                    'oauth_yandex_display_name' => 'Яндекс Пользователь',
                    'oauth_yandex_avatar_url' => null,
                ]);
        });

        $state = base64_encode(Crypt::encryptString(json_encode([
            'project_id' => 42,
            'cache_data_id' => 'lw-metrika-test',
            'popup' => false,
        ])));

        $response = $this->actingAs($user)->get(route('yandex-metrika.callback', [
            'code' => 'auth-code',
            'state' => $state,
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('clients-and-projects/project', $location);

        parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $query);
        $decoded = json_decode(
            Crypt::decryptString(base64_decode($query['state'])),
            true
        );
        $this->assertSame('yandex_metrika', $decoded['open_integration'] ?? null);
        $integrationSettings = $decoded['integrations'][0]['settings'] ?? [];
        $this->assertSame(
            'access-token',
            $integrationSettings['oauth_token'] ?? null
        );
        $this->assertArrayNotHasKey('counter_id', $integrationSettings);
    }

    #[Test]
    public function test_oauth_callback_popup_writes_local_storage_done_key(): void
    {
        $user = User::factory()->create();

        $this->mock(YandexMetrikaAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->andReturn([
                    'access_token' => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'expires_in' => 3600,
                ]);
            $mock->shouldReceive('fetchOauthUserProfile')
                ->once()
                ->andReturn([
                    'oauth_yandex_user_id' => '100500',
                    'oauth_yandex_login' => 'yandex-user',
                    'oauth_yandex_display_name' => 'Яндекс Пользователь',
                    'oauth_yandex_avatar_url' => null,
                ]);
        });

        $state = base64_encode(Crypt::encryptString(json_encode([
            'project_id' => 1,
            'cache_data_id' => 'lw-metrika-storage-test',
            'popup' => true,
        ])));

        $response = $this->actingAs($user)->get(route('yandex-metrika.callback', [
            'code' => 'auth-code',
            'state' => $state,
        ]));

        $response->assertOk();
        $response->assertSee('casini_yandex_metrika_oauth_done', false);
        $response->assertSee('lw-metrika-storage-test', false);
        $response->assertSee('localStorage', false);
        $response->assertSee('3000', false);
    }
}
